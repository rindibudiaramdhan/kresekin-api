<?php

namespace App\Support;

class OrderTimeOptionCatalog
{
    public const NOW = 'sekarang';

    public const SCHEDULED = 'jadwalkan';

    public static function all(): array
    {
        return [
            self::NOW => [
                'id' => 1,
                'code' => self::NOW,
                'name' => 'Sekarang',
                'description' => 'estimasi 15-30 menit',
            ],
            self::SCHEDULED => [
                'id' => 2,
                'code' => self::SCHEDULED,
                'name' => 'Jadwalkan',
                'description' => null,
            ],
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function find(?string $code): ?array
    {
        if (! $code) {
            return null;
        }

        return self::all()[$code] ?? null;
    }
}
