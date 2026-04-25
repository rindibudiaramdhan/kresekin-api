<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Kios Segar Bandung',
                'profile_picture_url' => asset('images/ic_vegetable_category.svg'),
                'rating' => 4.8,
                'category' => Tenant::CATEGORY_VEGETABLES,
                'latitude' => -6.914744,
                'longitude' => 107.609810,
                'open_time' => '07:00',
                'close_time' => '20:00',
                'products' => [
                    ['name' => 'Bayam Hijau', 'category' => 'Sayur', 'image_url' => asset('images/ic_vegetable_category.svg'), 'price' => 8000],
                    ['name' => 'Wortel Organik', 'category' => 'Sayur', 'image_url' => asset('images/ic_vegetable_category.svg'), 'price' => 12000],
                    ['name' => 'Apel Merah', 'category' => 'Buah', 'image_url' => asset('images/ic_fruit_category.svg'), 'price' => 18000],
                ],
            ],
            [
                'name' => 'Daging Prima',
                'profile_picture_url' => asset('images/ic_meat_category.svg'),
                'rating' => 4.7,
                'category' => Tenant::CATEGORY_MEAT,
                'latitude' => -6.897000,
                'longitude' => 107.610500,
                'open_time' => '08:00',
                'close_time' => '21:00',
                'products' => [
                    ['name' => 'Daging Sapi Potong', 'category' => 'Daging', 'image_url' => asset('images/ic_meat_category.svg'), 'price' => 95000],
                    ['name' => 'Daging Ayam Fillet', 'category' => 'Daging', 'image_url' => asset('images/ic_meat_category.svg'), 'price' => 42000],
                    ['name' => 'Sosis Beku', 'category' => 'Frozen Food', 'image_url' => asset('images/ic_frozen_food_category.svg'), 'price' => 28000],
                ],
            ],
            [
                'name' => 'Rumah Tangga Jaya',
                'profile_picture_url' => asset('images/ic_home_care_category.svg'),
                'rating' => 4.6,
                'category' => Tenant::CATEGORY_HOME_CARE,
                'latitude' => -6.920000,
                'longitude' => 107.615000,
                'open_time' => '09:00',
                'close_time' => '19:00',
                'products' => [
                    ['name' => 'Sabun Cuci Piring', 'category' => 'Home Care', 'image_url' => asset('images/ic_home_care_category.svg'), 'price' => 15000],
                    ['name' => 'Pembersih Lantai', 'category' => 'Home Care', 'image_url' => asset('images/ic_home_care_category.svg'), 'price' => 22000],
                    ['name' => 'Tisu Gulung', 'category' => 'Toiletries', 'image_url' => asset('images/ic_toiletries_category.svg'), 'price' => 12000],
                ],
            ],
            [
                'name' => 'Serba Ada Mart',
                'profile_picture_url' => asset('images/ic_groceries_category.svg'),
                'rating' => 4.9,
                'category' => Tenant::CATEGORY_GROCERIES,
                'latitude' => -6.910500,
                'longitude' => 107.600100,
                'open_time' => '06:00',
                'close_time' => '22:00',
                'products' => [
                    ['name' => 'Beras Premium', 'category' => 'Sembako', 'image_url' => asset('images/ic_groceries_category.svg'), 'price' => 68000],
                    ['name' => 'Gula Pasir', 'category' => 'Sembako', 'image_url' => asset('images/ic_groceries_category.svg'), 'price' => 17000],
                    ['name' => 'Mie Instan', 'category' => 'Makanan', 'image_url' => asset('images/ic_food_category.svg'), 'price' => 3500],
                    ['name' => 'Teh Botol', 'category' => 'Minuman', 'image_url' => asset('images/ic_drink_category.svg'), 'price' => 5000],
                ],
            ],
        ];

        foreach ($tenants as $tenantData) {
            $products = $tenantData['products'];
            unset($tenantData['products']);

            $tenant = Tenant::query()->updateOrCreate(
                ['name' => $tenantData['name']],
                $tenantData
            );

            foreach ($products as $productData) {
                Product::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $productData['name'],
                    ],
                    [
                        'category' => $productData['category'],
                        'image_url' => $productData['image_url'],
                        'price' => $productData['price'],
                        'original_price' => $productData['original_price'] ?? null,
                        'weight_label' => $productData['weight_label'] ?? null,
                        'description' => $productData['description'] ?? null,
                        'delivery_estimate' => $productData['delivery_estimate'] ?? null,
                    ]
                );
            }
        }
    }
}
