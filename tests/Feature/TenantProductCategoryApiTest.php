<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProductCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_get_unique_active_product_categories_for_a_tenant(): void
    {
        $token = $this->createAuthenticatedBuyer();
        $tenant = $this->createTenant('Toko Berkah');
        $otherTenant = $this->createTenant('Toko Lain');

        $groceries = $this->createCategory(Tenant::CATEGORY_GROCERIES, 'sembako');
        $beverages = $this->createCategory(Tenant::CATEGORY_BEVERAGES, 'minuman');
        $vegetables = $this->createCategory(Tenant::CATEGORY_VEGETABLES, 'sayur');
        $toiletries = $this->createCategory(Tenant::CATEGORY_TOILETRIES, 'toiletries');

        $this->createProduct($tenant, 'Beras', $groceries->name);
        $this->createProduct($tenant, 'Minyak Goreng', $groceries->name);
        $this->createProduct($tenant, 'Teh Botol', $beverages->name);
        $this->createProduct($tenant, 'Bayam Nonaktif', $vegetables->name, false);
        $this->createProduct($otherTenant, 'Sabun Mandi', $toiletries->name);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tenants/'.$tenant->id.'/product-categories');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar kategori barang merchant berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $beverages->id)
            ->assertJsonPath('data.0.name', Tenant::CATEGORY_BEVERAGES)
            ->assertJsonPath('data.0.slug', 'minuman')
            ->assertJsonPath('data.0.image_path', 'images/minuman.svg')
            ->assertJsonPath('data.1.id', $groceries->id)
            ->assertJsonPath('data.1.name', Tenant::CATEGORY_GROCERIES)
            ->assertJsonPath('data.1.slug', 'sembako');
    }

    public function test_tenant_without_active_products_returns_an_empty_category_list(): void
    {
        $token = $this->createAuthenticatedBuyer();
        $tenant = $this->createTenant('Toko Kosong');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tenants/'.$tenant->id.'/product-categories');

        $response
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_product_category_list_returns_not_found_for_unknown_tenant(): void
    {
        $token = $this->createAuthenticatedBuyer();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tenants/11111111-1111-1111-1111-111111111111/product-categories');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Merchant tidak ditemukan.');
    }

    public function test_product_category_list_returns_not_found_for_invalid_tenant_id(): void
    {
        $token = $this->createAuthenticatedBuyer();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tenants/invalid-id/product-categories');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Merchant tidak ditemukan.');
    }

    public function test_product_category_list_requires_buyer_authentication(): void
    {
        $tenant = $this->createTenant('Toko Berkah');

        $this->getJson('/api/tenants/'.$tenant->id.'/product-categories')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    private function createAuthenticatedBuyer(): string
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'buyer-tenant-categories@example.com',
            'phone' => '+6281234567800',
            'type' => 'phone',
            'role' => User::ROLE_BUYER,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $token = 'tenant-product-categories-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        return $token;
    }

    private function createTenant(string $name): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'rating' => 4.8,
            'category' => Tenant::CATEGORY_GROCERIES,
        ]);
    }

    private function createCategory(string $name, string $slug): ProductCategory
    {
        return ProductCategory::query()->create([
            'name' => $name,
            'slug' => $slug,
            'image_path' => 'images/'.$slug.'.svg',
        ]);
    }

    private function createProduct(Tenant $tenant, string $name, string $category, bool $isActive = true): Product
    {
        return Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'category' => $category,
            'price' => 10000,
            'is_active' => $isActive,
        ]);
    }
}
