<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_and_list_own_tenant(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller@example.com', '+6281200000001', 'seller-token', User::ROLE_SELLER);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/tenants', [
                'name' => 'Tenant Seller',
                'category' => Tenant::CATEGORY_GROCERIES,
                'profile_picture_url' => 'https://example.com/seller-tenant.png',
                'latitude' => -6.2,
                'longitude' => 106.8,
                'open_time' => '07:00',
                'close_time' => '21:00',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.owner_user_id', $seller->id)
            ->assertJsonPath('data.name', 'Tenant Seller');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.owner_user_id', $seller->id)
            ->assertJsonPath('data.0.name', 'Tenant Seller');
    }

    public function test_seller_can_create_and_list_own_product(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller2@example.com', '+6281200000002', 'seller-token-2', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Seller Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'category' => Tenant::CATEGORY_VEGETABLES,
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'original_price' => 9000,
                'weight_label' => '250gr',
                'description' => 'Sayur segar.',
                'delivery_estimate' => 'Hari ini',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.name', 'Bayam');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.tenant_id', $tenant->id)
            ->assertJsonPath('data.0.name', 'Bayam');
    }

    public function test_seller_cannot_create_product_for_other_sellers_tenant(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller3@example.com', '+6281200000003', 'seller-token-3', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller4@example.com', '+6281200000004', 'seller-token-4', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Tenant',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'category' => Tenant::CATEGORY_VEGETABLES,
                'price' => 7000,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_buyer_cannot_access_seller_endpoints(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer@example.com', '+6281200000005', 'buyer-token', User::ROLE_BUYER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh user dengan role seller.');
    }

    public function test_seller_cannot_access_buyer_checkout_endpoint(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller5@example.com', '+6281200000006', 'seller-token-5', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh user dengan role buyer.');
    }

    private function createAuthenticatedUser(string $email, string $phone, string $plainTextToken, string $role): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => $email,
            'phone' => $phone,
            'type' => User::AUTH_TYPE_PHONE,
            'role' => $role,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
