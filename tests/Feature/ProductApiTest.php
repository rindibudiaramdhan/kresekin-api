<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_paginated_product_list_with_default_limit(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Toko Aminah',
            'profile_picture_url' => 'https://example.com/aminah.png',
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $plainTextToken = 'product-list-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 12) as $index) {
            Product::query()->create([
                'tenant_id' => $tenant->id,
                'name' => sprintf('Produk %02d', $index),
                'category' => Tenant::CATEGORY_VEGETABLES,
                'image_url' => sprintf('https://example.com/product-%02d.png', $index),
                'price' => 9999,
                'original_price' => 15000,
                'weight_label' => '500gr',
                'description' => 'Produk segar.',
                'delivery_estimate' => '1-2 jam delivery',
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.tenant_name', 'Toko Aminah')
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_VEGETABLES)
            ->assertJsonPath('data.0.price_label', 'Rp 9.999')
            ->assertJsonPath('data.0.original_price_label', 'Rp 15.000')
            ->assertJsonPath('data.0.discount_percentage', 33);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_product_list_can_be_filtered_by_category_tenant_and_name(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $tenantA = Tenant::query()->create([
            'name' => 'Toko Aminah',
            'profile_picture_url' => null,
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Toko Asep',
            'profile_picture_url' => null,
            'rating' => 4.7,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $plainTextToken = 'product-filter-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        Product::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Pakcoy',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => null,
            'price' => 9999,
            'original_price' => 15000,
            'weight_label' => '500gr',
            'description' => 'Produk segar.',
            'delivery_estimate' => '1-2 jam delivery',
        ]);

        Product::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Sabun Mandi',
            'category' => Tenant::CATEGORY_TOILETRIES,
            'image_url' => null,
            'price' => 18000,
            'original_price' => null,
            'weight_label' => null,
            'description' => 'Produk kebersihan.',
            'delivery_estimate' => '1-2 jam delivery',
        ]);

        Product::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Sawi Putih',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => null,
            'price' => 12000,
            'original_price' => null,
            'weight_label' => '1kg',
            'description' => 'Produk segar.',
            'delivery_estimate' => '1-2 jam delivery',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/products?category='.urlencode(Tenant::CATEGORY_VEGETABLES).'&tenant_id='.$tenantA->id.'&name=pak');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Pakcoy')
            ->assertJsonPath('data.0.tenant_id', $tenantA->id)
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_VEGETABLES);
    }

    public function test_product_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/products');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_authenticated_user_gets_not_found_when_product_detail_does_not_exist(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/products/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Barang tidak ditemukan.');
    }

    private function createAuthenticatedUser(): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'product-detail-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
