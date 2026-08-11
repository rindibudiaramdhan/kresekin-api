<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserSessionToken;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerMonitoringApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private string $token;

    private User $sellerA;

    private User $sellerB;

    private User $outsideSeller;

    private Tenant $storeA;

    private Tenant $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-06 12:00:00', 'Asia/Jakarta'));
        [$this->owner, $this->token] = $this->authenticatedUser('owner@example.com', User::ROLE_OWNER, 'owner-token');
        [$otherOwner] = $this->authenticatedUser('other-owner@example.com', User::ROLE_OWNER, 'other-owner-token');
        [$this->sellerA] = $this->authenticatedUser('seller-a@example.com', User::ROLE_SELLER, 'seller-a-token');
        [$this->sellerB] = $this->authenticatedUser('seller-b@example.com', User::ROLE_SELLER, 'seller-b-token');
        [$this->outsideSeller] = $this->authenticatedUser('outside@example.com', User::ROLE_SELLER, 'outside-token');

        $this->sellerA->update(['name' => 'Cabang A', 'branch_owner_user_id' => $this->owner->id]);
        $this->sellerB->update(['name' => 'Cabang B', 'branch_owner_user_id' => $this->owner->id]);
        $this->outsideSeller->update(['name' => 'Cabang Luar', 'branch_owner_user_id' => $otherOwner->id]);

        $this->storeA = Tenant::query()->create(['owner_user_id' => $this->sellerA->id, 'name' => 'Toko A', 'category' => Tenant::CATEGORY_GROCERIES]);
        $this->storeB = Tenant::query()->create(['owner_user_id' => $this->sellerB->id, 'name' => 'Toko B', 'category' => Tenant::CATEGORY_GROCERIES]);
        $outsideStore = Tenant::query()->create(['owner_user_id' => $this->outsideSeller->id, 'name' => 'Toko Luar', 'category' => Tenant::CATEGORY_GROCERIES]);

        $completed = $this->transaction('ORDER-001', Transaction::STATUS_COMPLETED, '2026-08-06 08:00:00');
        $this->item($completed, $this->storeA, 2, 100000);
        $this->item($completed, $this->storeB, 3, 200000);
        $this->item($completed, $outsideStore, 5, 500000);

        $processing = $this->transaction('ORDER-002', Transaction::STATUS_PROCESSING, '2026-08-06 10:00:00');
        $this->item($processing, $this->storeA, 1, 50000);

        $outside = $this->transaction('ORDER-OUTSIDE', Transaction::STATUS_COMPLETED, '2026-08-06 11:00:00');
        $this->item($outside, $outsideStore, 1, 900000);

        $previousDay = $this->transaction('ORDER-PREVIOUS', Transaction::STATUS_COMPLETED, '2026-08-05 23:59:59');
        $this->item($previousDay, $this->storeA, 1, 700000);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_summary_aggregates_all_assigned_branches_without_double_count_or_outside_data(): void
    {
        $this->ownerGet('/api/owner/online-monitoring/summary?date=2026-08-06')
            ->assertOk()
            ->assertJsonPath('data.summary.sales_amount', 300000)
            ->assertJsonPath('data.summary.order_count', 1)
            ->assertJsonPath('data.summary.item_quantity', 5)
            ->assertJsonPath('data.summary.active_store_count', 2)
            ->assertJsonCount(2, 'data.scope.branches')
            ->assertJsonCount(2, 'data.scope.stores')
            ->assertJsonMissing(['buyer' => []]);
    }

    public function test_global_branch_filter_changes_summary_and_keeps_status_counts_unfiltered_by_order_status(): void
    {
        $response = $this->ownerGet('/api/owner/online-monitoring/summary?date=2026-08-06&seller_id='.$this->sellerA->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.sales_amount', 100000)
            ->assertJsonPath('data.summary.order_count', 1)
            ->assertJsonPath('data.summary.item_quantity', 2)
            ->assertJsonPath('data.summary.active_store_count', 1);

        $counts = collect($response->json('data.order_status_counts'))->pluck('count', 'status_code');
        $this->assertSame(1, $counts[Transaction::STATUS_CODE_COMPLETED]);
        $this->assertSame(1, $counts[Transaction::STATUS_CODE_PROCESSING]);
    }

    public function test_orders_show_one_scoped_row_per_transaction_and_status_search_only_filter_orders(): void
    {
        $response = $this->ownerGet('/api/owner/online-monitoring/orders?date=2026-08-06&seller_id='.$this->sellerA->id.'&status=completed&search=001');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_number', 'ORDER-001')
            ->assertJsonPath('data.0.amount', 100000)
            ->assertJsonPath('data.0.branches.0.id', $this->sellerA->id)
            ->assertJsonPath('data.0.stores.0.id', $this->storeA->id)
            ->assertJsonCount(1, 'data.0.branches')
            ->assertJsonCount(1, 'data.0.stores')
            ->assertJsonMissingPath('data.0.buyer');
    }

    public function test_store_performance_uses_completed_metrics_but_latest_order_time_from_all_statuses(): void
    {
        $this->ownerGet('/api/owner/online-monitoring/stores?date=2026-08-06&seller_id='.$this->sellerA->id)
            ->assertOk()
            ->assertJsonPath('data.0.store_id', $this->storeA->id)
            ->assertJsonPath('data.0.sales_amount', 100000)
            ->assertJsonPath('data.0.order_count', 1)
            ->assertJsonPath('data.0.item_quantity', 2)
            ->assertJsonPath('data.0.last_order_at_label', '06 Agt 2026, 10:00 WIB');
    }

    public function test_store_performance_paginates_and_validates_query_contract(): void
    {
        $this->ownerGet('/api/owner/online-monitoring/stores?date=2026-08-06&per_page=1&page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(1, 'data');

        $this->ownerGet('/api/owner/online-monitoring/stores?date=06-08-2026&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'per_page']);
    }

    public function test_outside_or_mismatched_scope_returns_not_found(): void
    {
        $this->ownerGet('/api/owner/online-monitoring/summary?date=2026-08-06&seller_id='.$this->outsideSeller->id)
            ->assertNotFound();

        $this->ownerGet('/api/owner/online-monitoring/summary?date=2026-08-06&seller_id='.$this->sellerA->id.'&store_id='.$this->storeB->id)
            ->assertNotFound();
    }

    public function test_monitoring_requires_owner_role_and_authentication(): void
    {
        $this->getJson('/api/owner/online-monitoring/summary')->assertUnauthorized();
        [, $sellerToken] = $this->authenticatedUser('blocked-seller@example.com', User::ROLE_SELLER, 'blocked-token');

        $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->getJson('/api/owner/online-monitoring/summary')
            ->assertForbidden();

        $this->ownerGet('/api/seller/dashboard')->assertForbidden();
        $this->ownerGet('/api/agent/dashboard')->assertForbidden();
        $this->ownerGet('/api/finance/dashboard')->assertForbidden();
    }

    private function ownerGet(string $url)
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token)->getJson($url);
    }

    private function authenticatedUser(string $email, string $role, string $token): array
    {
        $user = User::query()->create([
            'name' => $email,
            'email' => $email,
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => $role,
        ]);
        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        return [$user, $token];
    }

    private function transaction(string $number, string $status, string $localTime): Transaction
    {
        $buyer = User::query()->create([
            'name' => 'Buyer '.$number,
            'email' => strtolower($number).'@example.com',
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => User::ROLE_BUYER,
        ]);

        return Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => $number,
            'status' => $status,
            'transaction_at' => CarbonImmutable::parse($localTime, 'Asia/Jakarta')->utc(),
        ]);
    }

    private function item(Transaction $transaction, Tenant $store, int $quantity, int $lineTotal): void
    {
        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'tenant_id' => $store->id,
            'product_name' => 'Produk '.$store->name,
            'quantity' => $quantity,
            'unit_price' => intdiv($lineTotal, $quantity),
            'line_total' => $lineTotal,
        ]);
    }
}
