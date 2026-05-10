<?php

namespace Database\Seeders;

use App\Models\OrderTimeOption;
use Illuminate\Database\Seeder;

class OrderTimeOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            [
                'code' => 'sekarang',
                'name' => 'Sekarang',
                'description' => 'estimasi 15-30 menit',
                'requires_schedule' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'jadwalkan',
                'name' => 'Jadwalkan',
                'description' => null,
                'requires_schedule' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($options as $option) {
            OrderTimeOption::query()->updateOrCreate(
                ['code' => $option['code']],
                $option
            );
        }
    }
}
