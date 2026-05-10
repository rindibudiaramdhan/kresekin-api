<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTimeOptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_order_time_options(): void
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

        $plainTextToken = 'order-time-option-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/order-time-options');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar pilihan waktu pesanan berhasil diambil.')
            ->assertJsonPath('data.0.code', 'sekarang')
            ->assertJsonPath('data.0.name', 'Sekarang')
            ->assertJsonPath('data.0.description', 'estimasi 15-30 menit')
            ->assertJsonPath('data.1.code', 'jadwalkan')
            ->assertJsonPath('data.1.name', 'Jadwalkan')
            ->assertJsonPath('data.1.description', null);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_order_time_options_requires_authentication(): void
    {
        $response = $this->getJson('/api/order-time-options');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }
}
