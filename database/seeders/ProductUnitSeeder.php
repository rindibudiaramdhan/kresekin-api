<?php

namespace Database\Seeders;

use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'buah',
            'ikat',
            'kilogram',
            'gram',
            'liter',
            'pcs',
            'pak',
            'pack',
            'botol',
            'kaleng',
            'dus',
            'sachet',
            'porsi',
            'meter',
            'roll',
        ];

        foreach ($units as $unit) {
            ProductUnit::query()->updateOrCreate(
                ['slug' => str($unit)->slug()->toString()],
                [
                    'name' => $unit,
                    'is_active' => true,
                ],
            );
        }
    }
}
