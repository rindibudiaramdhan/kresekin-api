<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_validate_existing_promo_code(): void
    {
        [$token] = $this->createAuthenticatedUser();

        PromoCode::query()->create([
            'code' => 'HEMAT10',
            'name' => 'Hemat 10%',
            'description' => 'Diskon 10% untuk pesanan minimal Rp 50.000.',
            'discount_type' => PromoCode::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_order_amount' => 50000,
            'maximum_discount_amount' => 10000,
            'quantity' => 20,
            'used_quantity' => 5,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', [
                'code' => 'hemat10',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Promo berhasil ditemukan.')
            ->assertJsonPath('data.code', 'HEMAT10')
            ->assertJsonPath('data.name', 'Hemat 10%')
            ->assertJsonPath('data.description', 'Diskon 10% untuk pesanan minimal Rp 50.000.')
            ->assertJsonPath('data.discount_type', 'percentage')
            ->assertJsonPath('data.discount_value', 10)
            ->assertJsonPath('data.discount_label', '10%')
            ->assertJsonPath('data.minimum_order_amount', 50000)
            ->assertJsonPath('data.minimum_order_amount_label', 'Rp 50.000')
            ->assertJsonPath('data.maximum_discount_amount', 10000)
            ->assertJsonPath('data.maximum_discount_amount_label', 'Rp 10.000')
            ->assertJsonPath('data.quantity', 20)
            ->assertJsonPath('data.used_quantity', 5)
            ->assertJsonPath('data.remaining_quantity', 15)
            ->assertJsonPath('data.is_active', true);
    }

    public function test_validate_promo_code_returns_not_found_when_code_does_not_exist(): void
    {
        [$token] = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', [
                'code' => 'TIDAKADA',
            ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Promo tidak ditemukan.');
    }

    public function test_validate_promo_code_returns_not_found_when_promo_is_inactive(): void
    {
        [$token] = $this->createAuthenticatedUser();

        PromoCode::query()->create([
            'code' => 'NONAKTIF',
            'name' => 'Promo Nonaktif',
            'discount_type' => PromoCode::DISCOUNT_TYPE_FIXED_AMOUNT,
            'discount_value' => 5000,
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', [
                'code' => 'NONAKTIF',
            ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Promo tidak ditemukan.');
    }

    public function test_validate_promo_code_returns_not_found_when_promo_is_outside_period(): void
    {
        [$token] = $this->createAuthenticatedUser();

        PromoCode::query()->create([
            'code' => 'EXPIRED',
            'name' => 'Promo Kedaluwarsa',
            'discount_type' => PromoCode::DISCOUNT_TYPE_FIXED_AMOUNT,
            'discount_value' => 5000,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', [
                'code' => 'EXPIRED',
            ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Promo tidak ditemukan.');
    }

    public function test_validate_promo_code_returns_not_found_when_promo_quantity_is_exhausted(): void
    {
        [$token] = $this->createAuthenticatedUser();

        PromoCode::query()->create([
            'code' => 'HABIS',
            'name' => 'Promo Habis',
            'discount_type' => PromoCode::DISCOUNT_TYPE_FIXED_AMOUNT,
            'discount_value' => 5000,
            'quantity' => 10,
            'used_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', [
                'code' => 'HABIS',
            ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Promo tidak ditemukan.');
    }

    public function test_validate_promo_code_requires_code(): void
    {
        [$token] = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/promo-codes/validate', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_validate_promo_code_requires_authentication(): void
    {
        $response = $this->postJson('/api/promo-codes/validate', [
            'code' => 'HEMAT10',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
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

        $plainTextToken = 'promo-code-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$plainTextToken, $user];
    }
}
