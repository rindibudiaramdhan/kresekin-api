<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceFeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_buyer_can_get_checkout_service_fee(): void
    {
        config()->set('api.service_fee', 1000);

        $token = $this->createBuyerToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/service-fee')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Biaya layanan berhasil diambil.',
                'data' => [
                    'service_fee' => 1000,
                    'service_fee_label' => 'Rp 1.000',
                ],
            ]);
    }

    public function test_service_fee_requires_authentication(): void
    {
        $this->getJson('/api/service-fee')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    public function test_service_fee_is_only_available_to_buyer(): void
    {
        $token = $this->createBuyerToken(User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/service-fee')
            ->assertForbidden();
    }

    private function createBuyerToken(string $role = User::ROLE_BUYER): string
    {
        $user = User::query()->create([
            'name' => 'Buyer Service Fee',
            'email' => $role.'-service-fee@example.com',
            'phone' => $role === User::ROLE_BUYER ? '+6281234500001' : '+6281234500002',
            'type' => User::AUTH_TYPE_PHONE,
            'role' => $role,
            'password' => null,
        ]);

        $token = $role.'-service-fee-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        return $token;
    }
}
