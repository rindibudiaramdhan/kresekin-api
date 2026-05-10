<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $promoCodes = [
            [
                'code' => 'HEMAT10',
                'name' => 'Hemat 10%',
                'description' => 'Diskon 10% untuk pesanan minimal Rp 50.000.',
                'discount_type' => PromoCode::DISCOUNT_TYPE_PERCENTAGE,
                'discount_value' => 10,
                'minimum_order_amount' => 50000,
                'maximum_discount_amount' => 10000,
                'quantity' => 100,
                'used_quantity' => 0,
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addMonth()->endOfDay(),
                'is_active' => true,
            ],
            [
                'code' => 'KRESEKIN5K',
                'name' => 'Potongan Rp 5.000',
                'description' => 'Potongan langsung Rp 5.000 untuk pesanan minimal Rp 25.000.',
                'discount_type' => PromoCode::DISCOUNT_TYPE_FIXED_AMOUNT,
                'discount_value' => 5000,
                'minimum_order_amount' => 25000,
                'maximum_discount_amount' => null,
                'quantity' => 200,
                'used_quantity' => 0,
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addMonth()->endOfDay(),
                'is_active' => true,
            ],
        ];

        foreach ($promoCodes as $promoCode) {
            PromoCode::query()->updateOrCreate(
                ['code' => $promoCode['code']],
                $promoCode
            );
        }
    }
}
