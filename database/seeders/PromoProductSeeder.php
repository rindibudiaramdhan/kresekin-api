<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use App\Support\ProductCategoryResolver;
use Illuminate\Database\Seeder;

class PromoProductSeeder extends Seeder
{
    public function run(): void
    {
        $promoProducts = [
            'Kios Segar Bandung' => [
                [
                    'name' => 'Paket Sayur Tumis',
                    'category' => Tenant::CATEGORY_VEGETABLES,
                    'image_url' => asset('images/ic_vegetable_category.svg'),
                    'price' => 18000,
                    'original_price' => 24000,
                    'weight_label' => '1 paket',
                    'description' => 'Paket sayur segar untuk menu tumisan harian.',
                    'delivery_estimate' => 'Hari ini',
                ],
                [
                    'name' => 'Apel Fuji Promo',
                    'category' => Tenant::CATEGORY_FRUITS,
                    'image_url' => asset('images/ic_fruit_category.svg'),
                    'price' => 22000,
                    'original_price' => 30000,
                    'weight_label' => '500gr',
                    'description' => 'Apel fuji segar dengan harga promo.',
                    'delivery_estimate' => 'Hari ini',
                ],
            ],
            'Daging Prima' => [
                [
                    'name' => 'Paket Ayam Fillet Hemat',
                    'category' => Tenant::CATEGORY_MEAT,
                    'image_url' => asset('images/ic_meat_category.svg'),
                    'price' => 38000,
                    'original_price' => 48000,
                    'weight_label' => '500gr',
                    'description' => 'Ayam fillet siap masak untuk stok harian.',
                    'delivery_estimate' => '1-2 jam delivery',
                ],
                [
                    'name' => 'Nugget Beku Promo',
                    'category' => Tenant::CATEGORY_FROZEN_FOOD,
                    'image_url' => asset('images/ic_frozen_food_category.svg'),
                    'price' => 26000,
                    'original_price' => 34000,
                    'weight_label' => '500gr',
                    'description' => 'Nugget beku praktis untuk lauk keluarga.',
                    'delivery_estimate' => '1-2 jam delivery',
                ],
            ],
            'Serba Ada Mart' => [
                [
                    'name' => 'Beras Premium Promo',
                    'category' => Tenant::CATEGORY_GROCERIES,
                    'image_url' => asset('images/ic_groceries_category.svg'),
                    'price' => 62000,
                    'original_price' => 72000,
                    'weight_label' => '5kg',
                    'description' => 'Beras premium pilihan dengan harga promo.',
                    'delivery_estimate' => 'Hari ini',
                ],
                [
                    'name' => 'Paket Minuman Hemat',
                    'category' => Tenant::CATEGORY_BEVERAGES,
                    'image_url' => asset('images/ic_drink_category.svg'),
                    'price' => 18000,
                    'original_price' => 24000,
                    'weight_label' => '4 pcs',
                    'description' => 'Paket minuman siap konsumsi untuk keluarga.',
                    'delivery_estimate' => 'Hari ini',
                ],
            ],
        ];

        foreach ($promoProducts as $tenantName => $products) {
            $tenant = Tenant::query()->where('name', $tenantName)->first();

            if (! $tenant) {
                continue;
            }

            foreach ($products as $productData) {
                $productCategory = app(ProductCategoryResolver::class)->resolve($productData['category']);
                if (! $productCategory) {
                    throw new \RuntimeException('Kategori fixture tidak cocok dengan master unik. Jalankan ProductCategorySeeder terlebih dahulu.');
                }
                $productData['product_category_id'] = $productCategory->id;
                $productData['category'] = $productCategory->name;
                Product::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $productData['name'],
                    ],
                    $productData
                );
            }
        }
    }
}
