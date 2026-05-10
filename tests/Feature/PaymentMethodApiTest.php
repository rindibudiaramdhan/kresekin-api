<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_payment_methods(): void
    {
        $this->seedPaymentMethods();

        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'payment-method-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/payment-methods');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.code', 'bank_transfer')
            ->assertJsonPath('data.0.name', 'Transfer Bank')
            ->assertJsonPath('data.0.requires_option', true)
            ->assertJsonPath('data.0.options.0.code', 'bca')
            ->assertJsonPath('data.0.options.0.name', 'BCA')
            ->assertJsonPath('data.0.options.1.code', 'mandiri')
            ->assertJsonPath('data.1.code', 'qr_payment')
            ->assertJsonPath('data.1.requires_option', false)
            ->assertJsonPath('data.2.code', 'cod');

        $this->assertCount(3, $response->json('data'));
    }

    public function test_payment_methods_requires_authentication(): void
    {
        $response = $this->getJson('/api/payment-methods');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    private function seedPaymentMethods(): void
    {
        $bankTransfer = PaymentMethod::query()->create([
            'code' => PaymentMethod::BANK_TRANSFER,
            'name' => 'Transfer Bank',
            'icon_key' => 'bank_transfer',
            'requires_option' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $bankTransfer->options()->createMany([
            [
                'code' => 'bca',
                'name' => 'BCA',
                'icon_key' => 'bank_bca',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'mandiri',
                'name' => 'Mandiri',
                'icon_key' => 'bank_mandiri',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ]);

        PaymentMethod::query()->create([
            'code' => PaymentMethod::QR_PAYMENT,
            'name' => 'QR Payment',
            'icon_key' => 'qris',
            'requires_option' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        PaymentMethod::query()->create([
            'code' => PaymentMethod::COD,
            'name' => 'COD',
            'icon_key' => 'cod',
            'requires_option' => false,
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }
}
