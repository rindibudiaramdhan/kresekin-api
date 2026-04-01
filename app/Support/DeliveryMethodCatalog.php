<?php

namespace App\Support;

class DeliveryMethodCatalog
{
    public const STORE_COURIER = 'store_courier';
    public const PICKUP = 'pickup';

    public static function all(): array
    {
        return [
            self::STORE_COURIER => [
                'id' => 1,
                'code' => self::STORE_COURIER,
                'name' => 'Antar Kurir Toko',
                'description' => 'Diantar hari ini',
                'fee' => 2500,
            ],
            self::PICKUP => [
                'id' => 2,
                'code' => self::PICKUP,
                'name' => 'Ambil ke Toko',
                'description' => null,
                'fee' => 0,
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
