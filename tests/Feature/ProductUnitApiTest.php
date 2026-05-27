<?php

namespace Tests\Feature;

use App\Models\ProductUnit;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_active_product_units(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        ProductUnit::query()->create([
            'name' => 'karung',
            'slug' => 'karung',
            'is_active' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/product-units')
            ->assertOk()
            ->assertJsonPath('message', 'Daftar satuan produk berhasil diambil.')
            ->assertJsonFragment(['name' => 'ikat', 'slug' => 'ikat'])
            ->assertJsonMissing(['name' => 'karung', 'slug' => 'karung']);
    }

    public function test_product_units_requires_authentication(): void
    {
        $this->getJson('/api/product-units')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    private function createAuthenticatedUser(): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-unit@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'product-unit-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
