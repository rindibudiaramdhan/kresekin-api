<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BpsRegionService
{
    /**
     * @return array<int, array<string, string|null>>
     */
    public function list(string $level, string $parent): array
    {
        $cacheKey = "bps_regions:{$level}:{$parent}";
        $ttl = (int) config('services.bps_regions.cache_ttl', 604800);

        return Cache::remember($cacheKey, $ttl, fn (): array => $this->fetch($level, $parent));
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function fetch(string $level, string $parent): array
    {
        try {
            $response = Http::baseUrl((string) config('services.bps_regions.base_url'))
                ->acceptJson()
                ->timeout((int) config('services.bps_regions.timeout', 10))
                ->retry(2, 200)
                ->get('/rest-bridging/getwilayah', [
                    'level' => $level,
                    'parent' => $parent,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Gagal mengambil data wilayah dari BPS.', previous: $exception);
        }

        $regions = $response->json();

        if (! is_array($regions)) {
            throw new RuntimeException('Format response wilayah BPS tidak valid.');
        }

        return collect($regions)
            ->filter(fn ($region): bool => is_array($region))
            ->map(fn (array $region): array => [
                'code' => $this->nullableString($region['kode_bps'] ?? null),
                'name' => $this->nullableString($region['nama_bps'] ?? null),
                'bps_code' => $this->nullableString($region['kode_bps'] ?? null),
                'bps_name' => $this->nullableString($region['nama_bps'] ?? null),
                'kemendagri_code' => $this->nullableString($region['kode_dagri'] ?? null),
                'kemendagri_name' => $this->nullableString($region['nama_dagri'] ?? null),
            ])
            ->filter(fn (array $region): bool => $region['code'] !== null && $region['name'] !== null)
            ->sortBy('code')
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
