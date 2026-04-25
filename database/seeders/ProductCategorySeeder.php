<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Sayur',
                'slug' => 'sayur',
                'image_path' => 'images/ic_vegetable_category.svg',
            ],
            [
                'name' => 'Buah',
                'slug' => 'buah',
                'image_path' => 'images/ic_fruit_category.svg',
            ],
            [
                'name' => 'Daging',
                'slug' => 'daging',
                'image_path' => 'images/ic_meat_category.svg',
            ],
            [
                'name' => 'Toiletries',
                'slug' => 'toiletries',
                'image_path' => 'images/ic_toiletries_category.svg',
            ],
            [
                'name' => 'Minuman',
                'slug' => 'minuman',
                'image_path' => 'images/ic_drink_category.svg',
            ],
            [
                'name' => 'Obat',
                'slug' => 'obat',
                'image_path' => 'images/ic_medicine_category.svg',
            ],
            [
                'name' => 'Makanan',
                'slug' => 'makanan',
                'image_path' => 'images/ic_food_category.svg',
            ],
            [
                'name' => 'Frozen Food',
                'slug' => 'frozen-food',
                'image_path' => 'images/ic_frozen_food_category.svg',
            ],
            [
                'name' => 'Bayi & Anak',
                'slug' => 'bayi-anak',
                'image_path' => 'images/ic_baby_kids_category.svg',
            ],
            [
                'name' => 'Home Care',
                'slug' => 'home-care',
                'image_path' => 'images/ic_home_care_category.svg',
            ],
            [
                'name' => 'Alat Tulis',
                'slug' => 'alat-tulis',
                'image_path' => 'images/ic_stationery_category.svg',
            ],
            [
                'name' => 'Bumbu Dapur',
                'slug' => 'bumbu-dapur',
                'image_path' => 'images/ic_seasoning_category.svg',
            ],
            [
                'name' => 'Personal Care',
                'slug' => 'personal-care',
                'image_path' => 'images/ic_personal_care_category.svg',
            ],
            [
                'name' => 'Peralatan Rumah',
                'slug' => 'peralatan-rumah',
                'image_path' => 'images/ic_home_equipment_category.svg',
            ],
            [
                'name' => 'Sembako',
                'slug' => 'sembako',
                'image_path' => 'images/ic_groceries_category.svg',
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'image_path' => $category['image_path'],
                ]
            );
        }
    }
}
