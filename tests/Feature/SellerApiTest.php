<?php

namespace Tests\Feature;

use App\Models\CancellationReasonCategory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Models\UserSessionToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_and_list_own_tenant(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

        [$seller, $token] = $this->createAuthenticatedUser('seller@example.com', '+6281200000001', 'seller-token', User::ROLE_SELLER);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/tenants', [
                'name' => 'Tenant Seller',
                'category' => Tenant::CATEGORY_GROCERIES,
                'profile_picture_url' => 'https://example.com/seller-tenant.png',
                'latitude' => -6.2,
                'longitude' => 106.8,
                'open_time' => '07:00',
                'close_time' => '21:00',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.owner_user_id', $seller->id)
            ->assertJsonPath('data.name', 'Tenant Seller');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.owner_user_id', $seller->id)
            ->assertJsonPath('data.0.name', 'Tenant Seller')
            ->assertJsonPath('data.0.is_open', true)
            ->assertJsonPath('data.0.store_status', 'Buka')
            ->assertJsonPath('data.0.operating_hours_label', 'Buka 07:00 sd 21:00');

        Carbon::setTestNow();
    }

    public function test_seller_can_create_and_list_own_product(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller2@example.com', '+6281200000002', 'seller-token-2', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Seller Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'category' => Tenant::CATEGORY_VEGETABLES,
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'original_price' => 9000,
                'weight_label' => '250gr',
                'description' => 'Sayur segar.',
                'delivery_estimate' => 'Hari ini',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.name', 'Bayam');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.tenant_id', $tenant->id)
            ->assertJsonPath('data.0.name', 'Bayam');
    }

    public function test_seller_cannot_create_product_for_other_sellers_tenant(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller3@example.com', '+6281200000003', 'seller-token-3', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller4@example.com', '+6281200000004', 'seller-token-4', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Tenant',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'category' => Tenant::CATEGORY_VEGETABLES,
                'price' => 7000,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_seller_can_list_and_view_own_orders(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-order@example.com', '+6281200000007', 'seller-order-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('other-seller-order@example.com', '+6281200000008', 'other-seller-order-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($seller, 'ORDER001', Transaction::STATUS_PROCESSING);
        $this->createOrderForSeller($otherSeller, 'ORDER002', Transaction::STATUS_PROCESSING);

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.order_number', 'ORDER001')
            ->assertJsonPath('data.0.status_code', Transaction::STATUS_CODE_PROCESSING)
            ->assertJsonPath('meta.total', 1);

        $detailResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id);

        $detailResponse
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', 'ORDER001')
            ->assertJsonPath('data.items.0.product_name', 'Produk ORDER001')
            ->assertJsonPath('data.status_timelines.0.status_code', Transaction::STATUS_CODE_PROCESSING);
    }

    public function test_seller_can_update_order_status_until_completed(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-status@example.com', '+6281200000009', 'seller-status-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($seller, 'ORDER003', Transaction::STATUS_ACCEPTED_BY_STORE);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_PROCESSING,
                'description' => 'Pesanan sedang disiapkan.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_ON_THE_WAY,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_ON_THE_WAY);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED);

        $order->refresh();

        $this->assertSame(Transaction::STATUS_COMPLETED, $order->status);
        $this->assertSame(4, $order->statusHistories()->count());
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $order->id,
            'status' => Transaction::STATUS_PROCESSING,
            'description' => 'Pesanan sedang disiapkan.',
        ]);
    }

    public function test_seller_can_cancel_order_with_cancellation_reason_category(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-cancel@example.com', '+6281200000012', 'seller-cancel-token', User::ROLE_SELLER);
        $category = CancellationReasonCategory::query()
            ->where('name', 'Salah Pesan / Salah Produk')
            ->firstOrFail();
        $order = $this->createOrderForSeller($seller, 'ORDER005', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_CANCELED)
            ->assertJsonPath('data.cancellation_reason.category_id', $category->id)
            ->assertJsonPath('data.cancellation_reason.category_name', 'Salah Pesan / Salah Produk');

        $this->assertDatabaseHas('transactions', [
            'id' => $order->id,
            'status' => Transaction::STATUS_CANCELED,
            'cancellation_reason_category_id' => $category->id,
            'cancellation_reason_text' => null,
        ]);
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $order->id,
            'status' => Transaction::STATUS_CANCELED,
            'description' => 'Pesanan dibatalkan. Alasan: Salah Pesan / Salah Produk',
        ]);
    }

    public function test_seller_cancel_order_requires_free_text_for_other_reason_category(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-cancel-other@example.com', '+6281200000013', 'seller-cancel-other-token', User::ROLE_SELLER);
        $otherReason = CancellationReasonCategory::query()
            ->where('name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->firstOrFail();
        $order = $this->createOrderForSeller($seller, 'ORDER006', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $otherReason->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cancellation_reason_text']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $otherReason->id,
                'cancellation_reason_text' => 'Buyer meminta pembatalan melalui chat.',
            ])
            ->assertOk()
            ->assertJsonPath('data.cancellation_reason.category_name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->assertJsonPath('data.cancellation_reason.reason_text', 'Buyer meminta pembatalan melalui chat.');
    }

    public function test_seller_cannot_manage_other_sellers_order(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-no-order@example.com', '+6281200000010', 'seller-no-order-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller-owner-order@example.com', '+6281200000011', 'seller-owner-order-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($otherSeller, 'ORDER004', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Order tidak ditemukan.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
            ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Order tidak ditemukan.');
    }

    public function test_buyer_cannot_access_seller_endpoints(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer@example.com', '+6281200000005', 'buyer-token', User::ROLE_BUYER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role seller.');
    }

    public function test_seller_cannot_access_buyer_checkout_endpoint(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller5@example.com', '+6281200000006', 'seller-token-5', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role buyer.');
    }

    private function createOrderForSeller(User $seller, string $orderNumber, string $status): Transaction
    {
        $buyer = User::query()->create([
            'name' => 'Buyer '.$orderNumber,
            'email' => 'buyer-'.strtolower($orderNumber).'@example.com',
            'phone' => '+628129'.substr(preg_replace('/\D/', '', $orderNumber), -4),
            'type' => User::AUTH_TYPE_PHONE,
            'role' => User::ROLE_BUYER,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant '.$orderNumber,
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_GROCERIES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk '.$orderNumber,
            'category' => Tenant::CATEGORY_GROCERIES,
            'price' => 12000,
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => $orderNumber,
            'status' => $status,
            'subtotal_amount' => 24000,
            'delivery_fee' => 5000,
            'total_amount' => 29000,
            'delivery_method' => 'Diantar',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 12000,
            'line_total' => 24000,
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => $status,
            'title' => 'Status awal',
            'description' => 'Status awal order',
            'sequence' => 1,
            'status_at' => now(),
        ]);

        return $transaction;
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
