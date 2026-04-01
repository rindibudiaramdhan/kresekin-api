<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_paginated_tenant_list_with_default_limit(): void
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
                'category' => Tenant::CATEGORY_TOILETRIES,
                'latitude' => -6.2000000,
                'longitude' => 106.8200000 + ($index / 1000),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.0.category_slug', 'toiletries')
            ->assertJsonPath('data.0.category_icon_key', 'toiletries')
            ->assertJsonPath('data.0.category_background_color', '#FFF5DF')
            ->assertJsonPath('data.0.category_icon_color', '#F4B544')
            ->assertJsonPath('data.0.rating', 4.5);

        $this->assertCount(10, $response->json('data'));
        $this->assertNotNull($response->json('data.0.distance_km'));
        $this->assertSame('km', substr((string) $response->json('data.0.distance_label'), -2));
    }

    public function test_tenant_list_respects_limit_query_parameter(): void
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
                'category' => Tenant::CATEGORY_GROCERIES,
                'latitude' => -6.2100000,
                'longitude' => 106.8260000 + ($index / 1000),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_GROCERIES)
            ->assertJsonPath('data.0.category_slug', 'sembako')
            ->assertJsonPath('data.0.category_icon_key', 'groceries');

        $this->assertCount(3, $response->json('data'));
    }

    public function test_tenant_list_can_be_filtered_by_category_and_sorted_by_nearest_distance(): void
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

        Tenant::query()->create([
            'name' => 'Toko Far',
            'profile_picture_url' => 'https://example.com/far.png',
            'rating' => 4.2,
            'category' => Tenant::CATEGORY_TOILETRIES,
            'latitude' => -6.2400000,
            'longitude' => 106.8500000,
        ]);

        Tenant::query()->create([
            'name' => 'Toko Near',
            'profile_picture_url' => 'https://example.com/near.png',
            'rating' => 4.9,
            'category' => Tenant::CATEGORY_TOILETRIES,
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
        ]);

        Tenant::query()->create([
            'name' => 'Toko Sembako',
            'profile_picture_url' => 'https://example.com/grocery.png',
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_GROCERIES,
            'latitude' => -6.2005000,
            'longitude' => 106.8165000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?category='.urlencode(Tenant::CATEGORY_TOILETRIES));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Toko Near')
            ->assertJsonPath('data.1.name', 'Toko Far')
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.1.category', Tenant::CATEGORY_TOILETRIES);

        $this->assertLessThan(
            $response->json('data.1.distance_km'),
            $response->json('data.0.distance_km')
        );
    }

    public function test_tenant_list_returns_validation_error_for_unknown_category(): void
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

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?category=Elektronik');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
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
}
