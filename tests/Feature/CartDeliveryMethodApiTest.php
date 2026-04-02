<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartDeliveryMethodApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_cart_delivery_method_requires_authentication(): void
    {
        $this->patchJson('/api/cart/delivery-method', [
            'delivery_method_code' => 'pickup',
        ])->assertUnauthorized();
    }

    public function test_update_cart_delivery_method_validates_invalid_code(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/cart/delivery-method', [
                'delivery_method_code' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delivery_method_code']);
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

        $plainTextToken = 'cart-delivery-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
