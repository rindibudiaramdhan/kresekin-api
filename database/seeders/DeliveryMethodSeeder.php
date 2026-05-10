<?php

namespace Database\Seeders;

use App\Models\DeliveryMethod;
use Illuminate\Database\Seeder;

class DeliveryMethodSeeder extends Seeder
{
    public function run(): void
    {
        $deliveryMethods = [
            [
                'code' => 'store_courier',
                'name' => 'Antar Kurir Toko',
                'description' => 'Diantar hari ini',
                'fee' => 2500,
                'requires_order_time' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'pickup',
                'name' => 'Ambil ke Toko',
                'description' => null,
                'fee' => 0,
                'requires_order_time' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($deliveryMethods as $deliveryMethod) {
            DeliveryMethod::query()->updateOrCreate(
                ['code' => $deliveryMethod['code']],
                $deliveryMethod
            );
        }
    }
}
