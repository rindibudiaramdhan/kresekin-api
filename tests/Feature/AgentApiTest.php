<?php

namespace Tests\Feature;

use App\Models\AgentCommissionWithdrawal;
use App\Models\HousingArea;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_access_dashboard_and_sellers_in_scope(): void
    {
        [$agent, $token] = $this->createAuthenticatedUser('agent@example.com', '+6281300000001', 'agent-token', User::ROLE_AGENT);
        [$otherAgent] = $this->createAuthenticatedUser('other-agent@example.com', '+6281300000002', 'other-agent-token', User::ROLE_AGENT);
        [$seller] = $this->createAuthenticatedUser('seller-agent@example.com', '+6281300000003', 'seller-agent-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller-other@example.com', '+6281300000004', 'seller-other-token', User::ROLE_SELLER);

        $tenant = $this->createTenantWithCompletedOrder($agent, $seller, 'AGENT001', 100000);
        $this->createTenantWithCompletedOrder($otherAgent, $otherSeller, 'AGENT002', 200000);

        $dashboardResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/dashboard');

        $dashboardResponse
            ->assertOk()
            ->assertJsonPath('data.summary.total_commission.value', 5000)
            ->assertJsonPath('data.summary.total_managed_umkm_transaction_amount.value', 100000)
            ->assertJsonPath('data.summary.total_managed_umkm.value', 1)
            ->assertJsonPath('data.seller_count', 1)
            ->assertJsonPath('data.stores.0.id', $tenant->id)
            ->assertJsonPath('data.stores.0.name', 'Tenant AGENT001')
            ->assertJsonPath('data.stores.0.transaction_count', 1)
            ->assertJsonPath('data.stores.0.agent_commission', 5000);

        $sellerListResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/sellers');

        $sellerListResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $seller->id)
            ->assertJsonPath('data.0.total_revenue', 100000)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_agent_dashboard_returns_distinct_managed_area_count(): void
    {
        [$agent, $token] = $this->createAuthenticatedUser('area-agent@example.com', '+6281300000011', 'area-agent-token', User::ROLE_AGENT);
        [$seller] = $this->createAuthenticatedUser('area-seller@example.com', '+6281300000012', 'area-seller-token', User::ROLE_SELLER);

        $tenant = $this->createTenantWithCompletedOrder($agent, $seller, 'AREA001', 100000);
        $areaOne = HousingArea::query()->create([
            'name' => 'Area Satu',
            'code' => 'A1',
            'city' => 'Bandung',
            'district' => 'Antapani',
            'subdistrict' => 'Antapani Wetan',
            'village_code' => '3273010001',
        ]);
        $areaTwo = HousingArea::query()->create([
            'name' => 'Area Dua',
            'code' => 'A2',
            'city' => 'Bandung',
            'district' => 'Antapani',
            'subdistrict' => 'Antapani Wetan',
            'village_code' => '3273010001',
        ]);
        $tenant->housingAreas()->attach([$areaOne->id, $areaTwo->id]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/dashboard?period=monthly')
            ->assertOk()
            ->assertJsonPath('data.summary.total_managed_areas.value', 2)
            ->assertJsonPath('data.summary.total_managed_areas.formatted', '2 Area');
    }

    public function test_agent_can_search_and_paginate_managed_umkm_performance(): void
    {
        [$agent, $token] = $this->createAuthenticatedUser('managed-agent@example.com', '+6281300000013', 'managed-agent-token', User::ROLE_AGENT);
        [$otherAgent] = $this->createAuthenticatedUser('managed-other-agent@example.com', '+6281300000014', 'managed-other-token', User::ROLE_AGENT);
        [$seller] = $this->createAuthenticatedUser('managed-seller@example.com', '+6281300000015', 'managed-seller-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('managed-other-seller@example.com', '+6281300000016', 'managed-other-seller-token', User::ROLE_SELLER);

        $alpha = $this->createTenantWithCompletedOrder($agent, $seller, 'ALPHA001', 200000);
        $this->createTenantWithCompletedOrder($agent, $seller, 'BETA001', 100000);
        $this->createTenantWithCompletedOrder($otherAgent, $otherSeller, 'ALPHA999', 999000);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/managed-umkm?period=monthly&search=ALPHA&page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('message', 'Performa UMKM binaan berhasil diambil.')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.0.total_transaction_amount', 200000)
            ->assertJsonPath('data.0.agent_commission', 10000)
            ->assertJsonPath('data.0.growth_label', 'Baru')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_non_agent_cannot_access_managed_umkm_performance(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer-managed@example.com', '+6281300000017', 'buyer-managed-token', User::ROLE_BUYER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/managed-umkm')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role agent.');
    }

    public function test_agent_cannot_view_seller_outside_scope(): void
    {
        [$agent, $token] = $this->createAuthenticatedUser('scope-agent@example.com', '+6281300000005', 'scope-agent-token', User::ROLE_AGENT);
        [$otherAgent] = $this->createAuthenticatedUser('outside-agent@example.com', '+6281300000006', 'outside-agent-token', User::ROLE_AGENT);
        [$seller] = $this->createAuthenticatedUser('outside-seller@example.com', '+6281300000007', 'outside-seller-token', User::ROLE_SELLER);

        $this->createTenantWithCompletedOrder($otherAgent, $seller, 'OUT001', 100000);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/sellers/'.$seller->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Seller tidak ditemukan.');
    }

    public function test_agent_can_update_profile_and_request_commission_withdrawal(): void
    {
        [$agent, $token] = $this->createAuthenticatedUser('withdraw-agent@example.com', '+6281300000008', 'withdraw-agent-token', User::ROLE_AGENT);
        [$seller] = $this->createAuthenticatedUser('withdraw-seller@example.com', '+6281300000009', 'withdraw-seller-token', User::ROLE_SELLER);

        $this->createTenantWithCompletedOrder($agent, $seller, 'WD001', 100000);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commission-withdrawals', [
                'amount' => 1000,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Lengkapi profil rekening agent sebelum mencairkan komisi.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/agent/profile', [
                'name' => 'Agent Payout',
                'email' => 'withdraw-agent@example.com',
                'phone' => '+6281300000008',
                'bank_name' => 'BCA',
                'bank_account_name' => 'Agent Payout',
                'bank_account_number' => '1234567890',
            ])
            ->assertOk()
            ->assertJsonPath('data.bank_name', 'BCA');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commission-withdrawals', [
                'amount' => 6000,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Saldo komisi agent tidak mencukupi.');

        $withdrawalResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commission-withdrawals', [
                'amount' => 3000,
                'note' => 'Cairkan sebagian komisi.',
            ]);

        $withdrawalResponse
            ->assertCreated()
            ->assertJsonPath('data.amount', 3000)
            ->assertJsonPath('data.status', AgentCommissionWithdrawal::STATUS_REQUESTED);

        $this->assertDatabaseHas('agent_commission_withdrawals', [
            'agent_user_id' => $agent->id,
            'amount' => 3000,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/profile')
            ->assertOk()
            ->assertJsonPath('data.available_commission', 2000)
            ->assertJsonPath('data.payout_profile_completed', true);
    }

    public function test_buyer_cannot_access_agent_endpoints(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer-agent@example.com', '+6281300000010', 'buyer-agent-token', User::ROLE_BUYER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role agent.');
    }

    private function createTenantWithCompletedOrder(User $agent, User $seller, string $orderNumber, int $lineTotal): Tenant
    {
        $tenant = Tenant::query()->create([
            'agent_user_id' => $agent->id,
            'owner_user_id' => $seller->id,
            'name' => 'Tenant '.$orderNumber,
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_GROCERIES,
        ]);

        $buyer = User::query()->create([
            'name' => 'Buyer '.$orderNumber,
            'email' => 'buyer-'.strtolower($orderNumber).'@example.com',
            'phone' => '+628139'.substr((string) abs(crc32($orderNumber)), 0, 8),
            'type' => User::AUTH_TYPE_PHONE,
            'role' => User::ROLE_BUYER,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk '.$orderNumber,
            'category' => Tenant::CATEGORY_GROCERIES,
            'price' => $lineTotal,
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => $orderNumber,
            'status' => Transaction::STATUS_COMPLETED,
            'subtotal_amount' => $lineTotal,
            'delivery_fee' => 0,
            'total_amount' => $lineTotal,
            'delivery_method' => 'Diantar',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
        ]);

        return $tenant;
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
