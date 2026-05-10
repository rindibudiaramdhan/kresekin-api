<?php

namespace App\Support;

class PromoCodeCatalog
{
    public const HEMAT10 = 'HEMAT10';

    public const KRESEKIN5K = 'KRESEKIN5K';

    public static function all(): array
    {
        return [
            self::HEMAT10 => [
                'id' => 1,
                'code' => self::HEMAT10,
                'name' => 'Hemat 10%',
                'description' => 'Diskon 10% untuk pesanan minimal Rp 50.000.',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'minimum_order_amount' => 50000,
                'maximum_discount_amount' => 10000,
            ],
            self::KRESEKIN5K => [
                'id' => 2,
                'code' => self::KRESEKIN5K,
                'name' => 'Potongan Rp 5.000',
                'description' => 'Potongan langsung Rp 5.000 untuk pesanan minimal Rp 25.000.',
                'discount_type' => 'fixed_amount',
                'discount_value' => 5000,
                'minimum_order_amount' => 25000,
                'maximum_discount_amount' => null,
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

        $normalizedCode = strtoupper(trim($code));

        return self::all()[$normalizedCode] ?? null;
    }
}
