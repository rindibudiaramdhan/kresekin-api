<?php

namespace Tests\Feature;

use App\Models\HousingArea;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_paginated_tenant_list_with_default_limit(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

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

        $plainTextToken = 'tenant-list-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 12) as $index) {
            Tenant::query()->create([
                'name' => sprintf('Tenant %02d', $index),
                'profile_picture_url' => sprintf('https://example.com/tenant-%02d.png', $index),
                'rating' => 4.5,
                'latitude' => -6.2000000,
                'longitude' => 106.8200000 + ($index / 1000),
                'open_time' => '07:00',
                'close_time' => '21:00',
            ]);
        }

        $this->assignListArea($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonMissingPath('data.0.category')
            ->assertJsonMissingPath('data.0.category_slug')
            ->assertJsonMissingPath('data.0.category_icon_key')
            ->assertJsonMissingPath('data.0.category_background_color')
            ->assertJsonMissingPath('data.0.category_icon_color')
            ->assertJsonPath('data.0.latitude', -6.2)
            ->assertJsonPath('data.0.longitude', 106.821)
            ->assertJsonPath('data.0.is_open', true)
            ->assertJsonPath('data.0.store_status', 'Buka')
            ->assertJsonPath('data.0.open_time', '07:00')
            ->assertJsonPath('data.0.close_time', '21:00')
            ->assertJsonPath('data.0.operating_hours_label', 'Buka 07:00 sd 21:00')
            ->assertJsonPath('data.0.map_marker.title', 'Tenant 01')
            ->assertJsonMissingPath('data.0.map_marker.subtitle')
            ->assertJsonPath('data.0.rating', 4.5);

        $this->assertCount(10, $response->json('data'));
        $this->assertNotNull($response->json('data.0.distance_km'));
        $this->assertSame('km', substr((string) $response->json('data.0.distance_label'), -2));

        Carbon::setTestNow();
    }

    public function test_tenant_list_respects_limit_query_parameter(): void
    {
        Carbon::setTestNow('2026-04-01 22:00:00');

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

        $plainTextToken = 'tenant-list-limit-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 5) as $index) {
            Tenant::query()->create([
                'name' => sprintf('Tenant %02d', $index),
                'profile_picture_url' => sprintf('https://example.com/tenant-%02d.png', $index),
                'rating' => 5.0,
                'latitude' => -6.2100000,
                'longitude' => 106.8260000 + ($index / 1000),
                'open_time' => '07:00',
                'close_time' => '21:00',
            ]);
        }

        $this->assignListArea($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonMissingPath('data.0.category')
            ->assertJsonMissingPath('data.0.category_slug')
            ->assertJsonMissingPath('data.0.category_icon_key')
            ->assertJsonPath('data.0.is_open', false)
            ->assertJsonPath('data.0.store_status', 'Tutup');

        $this->assertCount(3, $response->json('data'));

        Carbon::setTestNow();
    }

    public function test_tenant_list_matches_any_service_area_to_the_authenticated_users_primary_area(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-area@example.com',
            'phone' => '+6281234567003',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'tenant-housing-area-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $targetArea = HousingArea::query()->create([
            'name' => 'Komp Setra Dago',
            'code' => 'AREA-001',
            'village_code' => '3273141003',
        ]);
        $otherArea = HousingArea::query()->create([
            'name' => 'Komp Antapani Indah',
            'code' => 'AREA-002',
            'village_code' => '3273141004',
        ]);

        $targetTenant = Tenant::query()->create([
            'name' => 'Toko Area Target',
            'profile_picture_url' => 'https://example.com/target-area.png',
            'rating' => 4.8,
            'is_open' => false,
            'open_time' => '00:00',
            'close_time' => '23:59',
        ]);
        $targetTenant->housingAreas()->sync([$otherArea->id, $targetArea->id]);

        $otherTenant = Tenant::query()->create([
            'name' => 'Toko Area Lain',
            'profile_picture_url' => 'https://example.com/other-area.png',
            'rating' => 4.1,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);
        $otherTenant->housingAreas()->sync([$otherArea->id]);

        $category = ProductCategory::query()->create([
            'name' => 'Buah',
            'slug' => 'buah',
            'image_path' => 'images/fruits.svg',
        ]);
        foreach ([$targetTenant, $otherTenant] as $tenant) {
            $product = new Product;
            $product->forceFill([
                'tenant_id' => $tenant->id,
                'name' => 'Apel',
                'category' => 'Buah',
                'price' => 10000,
            ])->save();
            $product->productCategories()->attach($category->id);
        }

        $user->housingAreas()->sync([$targetArea->id, $otherArea->id]);

        $this->withToken($plainTextToken)
            ->getJson('/api/tenants')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);

        $user->update(['housing_area_id' => $targetArea->id]);

        $urls = [
            '/api/tenants',
            '/api/tenants?product_category=buah&housing_area_id='.$otherArea->id,
        ];

        foreach ($urls as $url) {
            $response = $this->withToken($plainTextToken)
                ->getJson($url)
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', $targetTenant->id)
                ->assertJsonPath('data.0.is_open', false)
                ->assertJsonPath('data.0.store_status', 'Tutup')
                ->assertJsonPath('data.0.operating_hours_label', 'Buka 00:00 sd 23:59')
                ->assertJsonPath('data.0.product_count', 1)
                ->assertJsonPath('data.0.products_count', 1)
                ->assertJsonPath('user.housing_area_id', $targetArea->id);

            $this->assertEqualsCanonicalizing(
                [$targetArea->village_code, $otherArea->village_code],
                array_column($response->json('data.0.housing_areas'), 'village_code'),
            );
        }
    }

    public function test_tenant_list_ignores_unknown_and_invalid_housing_area_query_values(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-invalid-area@example.com',
            'phone' => '+6281234567004',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'tenant-invalid-housing-area-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken);
        $expected = $this->getJson('/api/tenants')->assertOk()->json();

        foreach (['11111111-1111-1111-1111-111111111111', 'invalid'] as $areaId) {
            $this->getJson('/api/tenants?housing_area_id='.$areaId)
                ->assertOk()
                ->assertExactJson($expected);
        }
    }

    public function test_tenant_list_filters_by_linked_product_category_slug(): void
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

        $plainTextToken = 'tenant-category-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $far = Tenant::query()->create([
            'name' => 'Toko Far',
            'profile_picture_url' => 'https://example.com/far.png',
            'rating' => 4.2,
            'latitude' => -6.2400000,
            'longitude' => 106.8500000,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);

        $near = Tenant::query()->create([
            'name' => 'Toko Near',
            'profile_picture_url' => 'https://example.com/near.png',
            'rating' => 4.9,
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);

        $groceries = Tenant::query()->create([
            'name' => 'Toko Sembako',
            'profile_picture_url' => 'https://example.com/grocery.png',
            'rating' => 5.0,
            'latitude' => -6.2005000,
            'longitude' => 106.8165000,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);

        $toiletriesCategory = ProductCategory::query()->create([
            'name' => 'Toiletries',
            'slug' => 'toiletries',
            'image_path' => 'images/toiletries.svg',
        ]);
        $groceriesCategory = ProductCategory::query()->create([
            'name' => 'Sembako',
            'slug' => 'sembako',
            'image_path' => 'images/groceries.svg',
        ]);
        foreach ([$far, $near, $groceries] as $tenant) {
            $product = new Product;
            $product->forceFill([
                'tenant_id' => $tenant->id,
                'name' => 'Produk '.$tenant->name,
                'category' => $tenant->id === $groceries->id ? 'Toiletries' : 'Sembako',
                'price' => 10000,
            ])->save();

            $product->productCategories()->attach($tenant->id === $groceries->id
                ? [$groceriesCategory->id]
                : [$groceriesCategory->id, $toiletriesCategory->id]);
        }

        $additionalProduct = new Product;
        $additionalProduct->forceFill([
            'tenant_id' => $far->id,
            'name' => 'Beras',
            'category' => 'Sembako',
            'price' => 10000,
        ])->save();
        $additionalProduct->productCategories()->attach($groceriesCategory->id);

        $this->assignListArea($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?product_category=toiletries');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissingPath('data.0.category')
            ->assertJsonMissingPath('data.1.category');

        $this->assertEqualsCanonicalizing([$far->id, $near->id], array_column($response->json('data'), 'id'));
        foreach ($response->json('data') as $tenant) {
            $this->assertSame(1, $tenant['product_count']);
            $this->assertSame($tenant['id'] === $far->id ? 2 : 1, $tenant['products_count']);
            $categories = collect($tenant['products'])->pluck('product_categories')->collapse();
            $this->assertContains('toiletries', $categories->pluck('slug')->all());
        }
        parse_str(parse_url($response->json('links.first'), PHP_URL_QUERY), $firstPageQuery);
        $this->assertSame('toiletries', $firstPageQuery['product_category']);
    }

    public function test_tenant_list_returns_validation_error_for_invalid_or_unknown_product_category_slug(): void
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

        $plainTextToken = 'tenant-invalid-category-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (['elektronik', 'Buah', 'frozen_food', ''] as $category) {
            $this->withToken($plainTextToken)
                ->getJson('/api/tenants?product_category='.$category)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['product_category']);
        }
    }

    public function test_tenant_without_coordinates_has_null_distance_and_is_sorted_last(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-null-distance@example.com',
            'phone' => '+6281234567002',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'tenant-null-distance-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        Tenant::query()->create([
            'name' => 'Toko Tanpa Koordinat',
            'profile_picture_url' => 'https://example.com/no-coordinates.png',
            'rating' => 4.1,
            'latitude' => null,
            'longitude' => null,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);

        Tenant::query()->create([
            'name' => 'Toko Dekat',
            'profile_picture_url' => 'https://example.com/near.png',
            'rating' => 4.8,
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
            'open_time' => '07:00',
            'close_time' => '21:00',
        ]);

        $this->assignListArea($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Toko Dekat')
            ->assertJsonPath('data.1.name', 'Toko Tanpa Koordinat')
            ->assertJsonPath('data.1.distance_km', null)
            ->assertJsonPath('data.1.distance_label', null)
            ->assertJsonPath('data.1.map_marker.latitude', null)
            ->assertJsonPath('data.1.map_marker.longitude', null);

        Carbon::setTestNow();
    }

    public function test_authenticated_user_can_get_tenant_categories(): void
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

        $plainTextToken = 'tenant-categories-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', Tenant::CATEGORY_VEGETABLES)
            ->assertJsonPath('data.0.slug', 'sayur')
            ->assertJsonPath('data.0.icon_key', 'vegetables')
            ->assertJsonPath('data.0.background_color', '#E7F6EB')
            ->assertJsonPath('data.0.icon_color', '#67B97A')
            ->assertJsonPath('data.3.name', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.3.icon_key', 'toiletries')
            ->assertJsonPath('data.14.name', Tenant::CATEGORY_GROCERIES);

        $this->assertCount(count(Tenant::CATEGORIES), $response->json('data'));
    }

    public function test_tenant_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/tenants');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_tenant_categories_requires_authentication(): void
    {
        $response = $this->getJson('/api/tenants/categories');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    private function assignListArea(User $user): void
    {
        $area = HousingArea::query()->create(['name' => 'Area buyer', 'code' => 'LIST-AREA']);
        $user->update(['housing_area_id' => $area->id]);

        Tenant::query()->each(fn (Tenant $tenant) => $tenant->housingAreas()->sync([$area->id]));
    }
}
