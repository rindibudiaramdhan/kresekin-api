<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_returns_not_found_when_product_does_not_exist(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/products/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Barang tidak ditemukan.');
    }

    public function test_product_detail_requires_authentication(): void
    {
        $this->getJson('/api/products/1')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
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
