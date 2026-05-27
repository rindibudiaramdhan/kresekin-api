<?php

namespace Tests\Feature;

use App\Models\CancellationReasonCategory;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationReasonCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_get_active_cancellation_reason_categories_with_other_reason(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer-cancel@example.com', '+6281500000001', 'buyer-cancel-token', User::ROLE_BUYER);

        CancellationReasonCategory::query()->create([
            'name' => 'Tidak Jadi Belanja',
            'sort_order' => 40,
            'allows_free_text' => false,
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cancellation-reason-categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Salah Pesan / Salah Produk')
            ->assertJsonPath('data.3.name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->assertJsonPath('data.3.allows_free_text', true)
            ->assertJsonPath('data.3.is_other_reason', true);

        $this->assertFalse(collect($response->json('data'))->contains('name', 'Tidak Jadi Belanja'));
    }

    public function test_seller_can_get_active_cancellation_reason_categories_for_rejecting_orders(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-cancel-list@example.com', '+6281500000005', 'seller-cancel-list-token', User::ROLE_SELLER);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/cancellation-reason-categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Salah Pesan / Salah Produk')
            ->assertJsonPath('data.3.name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->assertJsonPath('data.3.allows_free_text', true)
            ->assertJsonPath('data.3.is_other_reason', true);
    }

    public function test_finance_can_manage_cancellation_reason_categories(): void
    {
        [, $token] = $this->createAuthenticatedUser('finance-cancel@example.com', '+6281500000002', 'finance-cancel-token', User::ROLE_FINANCE);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/finance/cancellation-reason-categories', [
                'name' => 'Harga berubah',
                'sort_order' => 25,
                'is_active' => true,
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Harga berubah')
            ->assertJsonPath('data.sort_order', 25);

        $categoryId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/finance/cancellation-reason-categories/'.$categoryId, [
                'name' => 'Harga pesanan berubah',
                'sort_order' => 26,
                'allows_free_text' => false,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Harga pesanan berubah')
            ->assertJsonPath('data.sort_order', 26);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/finance/cancellation-reason-categories/'.$categoryId)
            ->assertOk()
            ->assertJsonPath('message', 'Kategori alasan pembatalan berhasil dihapus.');
    }

    public function test_other_reason_category_cannot_be_deleted_or_disabled(): void
    {
        [, $token] = $this->createAuthenticatedUser('finance-other@example.com', '+6281500000003', 'finance-other-token', User::ROLE_FINANCE);
        $otherReason = CancellationReasonCategory::query()
            ->where('name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/finance/cancellation-reason-categories/'.$otherReason->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kategori sistem Alasan Lainnya tidak dapat dihapus.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/finance/cancellation-reason-categories/'.$otherReason->id, [
                'name' => CancellationReasonCategory::OTHER_REASON_NAME,
                'sort_order' => 999,
                'allows_free_text' => true,
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    public function test_seller_cannot_manage_cancellation_reason_categories(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-cancel@example.com', '+6281500000004', 'seller-cancel-token', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/cancellation-reason-categories')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role finance.');
    }

    private function createAuthenticatedUser(string $email, string $phone, string $plainTextToken, string $role): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => $email,
            'phone' => $phone,
            'type' => User::AUTH_TYPE_PHONE,
            'role' => $role,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
