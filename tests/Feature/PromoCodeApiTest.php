<?php

namespace Tests\Feature;

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
            ->assertJsonPath('data.maximum_discount_amount_label', 'Rp 10.000');
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
