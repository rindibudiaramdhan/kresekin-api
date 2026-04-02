<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartEmptyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_cart_summary_when_cart_has_no_items(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.subtotal', 0)
            ->assertJsonPath('data.delivery_fee', 0)
            ->assertJsonPath('data.grand_total', 0)
            ->assertJsonPath('data.total_items', 0);
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

        $plainTextToken = 'empty-cart-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
