<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BpsRegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class GetIndonesiaRegionListController extends Controller
{
    public function __invoke(Request $request, BpsRegionService $regions): JsonResponse
    {
        $level = (string) $request->route('level');
        $config = $this->levelConfig($level);
        $parent = $this->parentCode($request, $config);

        try {
            $data = $regions->list($config['bps_level'], $parent);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Data wilayah BPS sedang tidak dapat diambil.',
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json([
            'message' => $config['message'],
            'data' => collect($data)
                ->map(fn (array $region): array => array_merge($region, $config['parent_field'] ? [
                    $config['parent_field'] => $parent,
                ] : []))
                ->values(),
        ]);
    }

    /**
     * @return array{bps_level: string, parent_query: string|null, parent_field: string|null, parent_pattern: string|null, message: string}
     */
    private function levelConfig(string $level): array
    {
        return match ($level) {
            'provinces' => [
                'bps_level' => 'provinsi',
                'parent_query' => null,
                'parent_field' => null,
                'parent_pattern' => null,
                'message' => 'Daftar provinsi berhasil diambil.',
            ],
            'regencies' => [
                'bps_level' => 'kabupaten',
                'parent_query' => 'province_code',
                'parent_field' => 'province_code',
                'parent_pattern' => '/^\d{2}$/',
                'message' => 'Daftar kabupaten/kota berhasil diambil.',
            ],
            'districts' => [
                'bps_level' => 'kecamatan',
                'parent_query' => 'regency_code',
                'parent_field' => 'regency_code',
                'parent_pattern' => '/^\d{4}$/',
                'message' => 'Daftar kecamatan berhasil diambil.',
            ],
            'villages' => [
                'bps_level' => 'desa',
                'parent_query' => 'district_code',
                'parent_field' => 'district_code',
                'parent_pattern' => '/^\d{7}$/',
                'message' => 'Daftar desa/kelurahan berhasil diambil.',
            ],
        };
    }

    /**
     * @param  array{parent_query: string|null, parent_pattern: string|null}  $config
     */
    private function parentCode(Request $request, array $config): string
    {
        if ($config['parent_query'] === null) {
            return '0';
        }

        $key = $config['parent_query'];
        $value = (string) $request->query($key, '');

        if ($value === '' || ! preg_match($config['parent_pattern'], $value)) {
            throw ValidationException::withMessages([
                $key => "Parameter {$key} wajib diisi dengan kode BPS yang valid.",
            ]);
        }

        return $value;
    }
}
