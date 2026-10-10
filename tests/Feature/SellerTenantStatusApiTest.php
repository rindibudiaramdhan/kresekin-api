<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerTenantStatusApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $role = User::ROLE_SELLER): User
    {
        $user = User::factory()->create(['role' => $role]);
        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'store-status-token'),
            'expires_at' => now()->addDay(),
        ]);
        $this->withToken('store-status-token');

        return $user;
    }

    public function test_owner_can_close_and_reopen_store_and_repeat_request(): void
    {
        $seller = $this->token();
        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Toko Test',
            'open_time' => '00:00',
            'close_time' => '23:59',
        ]);

        foreach ([false, false, true] as $isOpen) {
            $this->patchJson('/api/seller/tenants/'.$tenant->id.'/status', ['is_open' => $isOpen])
                ->assertOk()
                ->assertJsonPath('data.id', $tenant->id)
                ->assertJsonPath('data.is_open', $isOpen)
                ->assertJsonPath('data.store_status', $isOpen ? 'Buka' : 'Tutup');
            $this->assertSame($isOpen, $tenant->fresh()->is_open);
            $this->getJson('/api/seller/tenants')->assertOk()->assertJsonPath('data.0.is_open', $isOpen);
            $seller->forceFill(['role' => User::ROLE_BUYER])->save();
            $this->getJson('/api/tenants')->assertOk()->assertJsonPath('data.0.is_open', $isOpen);
            $seller->forceFill(['role' => User::ROLE_SELLER])->save();
        }
    }

    public function test_manual_open_overrides_missing_or_closed_operating_hours(): void
    {
        $seller = $this->token();
        $tenant = Tenant::query()->create(['owner_user_id' => $seller->id, 'name' => 'Toko Test']);
        $this->assertFalse($tenant->isOpenAt('12:00'));
        $this->patchJson('/api/seller/tenants/'.$tenant->id.'/status', ['is_open' => true])->assertOk();
        $this->assertTrue($tenant->fresh()->isOpenAt('12:00'));
        $tenant->forceFill(['open_time' => '07:00', 'close_time' => '08:00'])->save();
        $this->assertTrue($tenant->fresh()->isOpenAt('12:00'));
    }

    public function test_other_seller_store_and_unknown_store_return_not_found(): void
    {
        $this->token();
        $tenant = Tenant::query()->create(['owner_user_id' => User::factory()->create()->id, 'name' => 'Other']);
        foreach ([$tenant->id, '00000000-0000-4000-8000-000000000000'] as $id) {
            $this->patchJson('/api/seller/tenants/'.$id.'/status', ['is_open' => false])->assertNotFound();
        }
        $this->assertNull($tenant->fresh()->is_open);
    }

    public function test_invalid_payload_does_not_change_status(): void
    {
        $seller = $this->token();
        $tenant = Tenant::query()->create(['owner_user_id' => $seller->id, 'name' => 'Test', 'is_open' => true]);
        foreach ([[], ['is_open' => null], ['is_open' => 'closed'], ['is_open' => []]] as $payload) {
            $this->patchJson('/api/seller/tenants/'.$tenant->id.'/status', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('is_open');
        }
        $this->assertTrue($tenant->fresh()->is_open);
    }

    public function test_authentication_is_required(): void
    {
        $this->patchJson('/api/seller/tenants/unknown/status', ['is_open' => true])->assertUnauthorized();
    }

    public function test_non_seller_cannot_change_status(): void
    {
        $this->token(User::ROLE_BUYER);
        $this->patchJson('/api/seller/tenants/unknown/status', ['is_open' => true])->assertForbidden();
    }
}
