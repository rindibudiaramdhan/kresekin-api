<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryMethodApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_delivery_methods(): void
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

        $plainTextToken = 'delivery-method-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/delivery-methods');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.code', 'store_courier')
            ->assertJsonPath('data.0.name', 'Antar Kurir Toko')
            ->assertJsonPath('data.0.description', 'Diantar hari ini')
            ->assertJsonPath('data.0.fee', 2500)
            ->assertJsonPath('data.0.fee_label', 'Rp 2.500')
            ->assertJsonPath('data.1.code', 'pickup')
            ->assertJsonPath('data.1.name', 'Ambil ke Toko')
            ->assertJsonPath('data.1.fee', 0)
            ->assertJsonPath('data.1.fee_label', 'Rp 0');

        $this->assertCount(2, $response->json('data'));
    }

    public function test_delivery_methods_requires_authentication(): void
    {
        $response = $this->getJson('/api/delivery-methods');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
