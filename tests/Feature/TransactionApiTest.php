<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_transaction_history_sorted_by_latest_and_paginated(): void
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

        $otherUser = User::query()->create([
            'name' => 'Siti',
            'email' => 'siti@example.com',
            'phone' => '+6282222222222',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-history-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Toko Aminah',
            'category' => Tenant::CATEGORY_GROCERIES,
        ]);

        foreach (range(1, 12) as $index) {
            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'order_number' => sprintf('TRX%04d', $index),
                'status' => 'Pesanan Selesai',
                'transaction_at' => now()->subMinutes(12 - $index),
            ]);

            if ($index === 12) {
                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'tenant_id' => $tenant->id,
                    'product_name' => 'Beras Pandan Wangi 5kg',
                    'quantity' => 2,
                    'unit_price' => 75000,
                    'line_total' => 150000,
                ]);
            }
        }

        Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => 'Dalam perjalanan',
            'transaction_at' => now()->addMinute(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.order_number', 'TRX0012')
            ->assertJsonPath('data.0.status_code', Transaction::STATUS_CODE_COMPLETED)
            ->assertJsonPath('data.0.store_name', 'Toko Aminah')
            ->assertJsonPath('data.0.total_items', 2)
            ->assertJsonPath('data.0.items.0.tenant_name', 'Toko Aminah')
            ->assertJsonPath('data.0.items.0.product_name', 'Beras Pandan Wangi 5kg')
            ->assertJsonPath('data.0.items.0.quantity', 2)
            ->assertJsonPath('data.0.items.0.unit_price', 75000)
            ->assertJsonPath('data.0.items.0.unit_price_label', 'Rp. 75.000')
            ->assertJsonPath('data.0.items.0.line_total', 150000)
            ->assertJsonPath('data.0.items.0.line_total_label', 'Rp. 150.000')
            ->assertJsonPath('data.1.order_number', 'TRX0011')
            ->assertJsonPath('data.9.order_number', 'TRX0003');

        $this->assertCount(10, $response->json('data'));
    }

    public function test_transaction_history_can_be_filtered_by_status_code(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-filter@example.com',
            'phone' => '+6281234567891',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-filter-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'PENDING001',
            'status' => Transaction::STATUS_PENDING_PAYMENT,
            'transaction_at' => now()->subMinute(),
        ]);

        Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETED001',
            'status' => Transaction::STATUS_COMPLETED,
            'transaction_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions?status_code=completed');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_number', 'COMPLETED001')
            ->assertJsonPath('data.0.status', Transaction::STATUS_COMPLETED)
            ->assertJsonPath('data.0.status_code', Transaction::STATUS_CODE_COMPLETED);
    }

    public function test_transaction_history_rejects_invalid_status_code_filter(): void
    {
        [, $plainTextToken] = $this->createAuthenticatedUser('transaction-invalid-filter-token');

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions?status_code=invalid');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_code']);
    }

    public function test_transaction_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/users/transactions');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    public function test_authenticated_user_can_get_transaction_detail(): void
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

        $otherUser = User::query()->create([
            'name' => 'Siti',
            'email' => 'siti@example.com',
            'phone' => '+628111111111',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-detail-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => '26032301CATSYR',
            'status' => Transaction::STATUS_PROCESSING,
            'total_amount' => 9999999,
            'delivery_method' => 'Antar Kurir Toko',
            'pickup_scheduled_at' => '15:00',
            'payment_method' => Transaction::PAYMENT_METHOD_BANK_TRANSFER,
            'transaction_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 0),
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Toko Segar Jaya',
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'tenant_id' => $tenant->id,
            'product_name' => 'Bayam Hijau',
            'quantity' => 3,
            'unit_price' => 5000,
            'line_total' => 15000,
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_PENDING_PAYMENT,
            'title' => 'Pembayaran Transfer Bank Lunas',
            'description' => 'Auto konfirmasi',
            'sequence' => 1,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 2),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
            'title' => 'Pesanan diterima',
            'description' => 'Langsung diproses',
            'sequence' => 2,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 3),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_PROCESSING,
            'title' => 'Pesanan sedang diproses',
            'description' => 'Toko memproses',
            'sequence' => 3,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 5),
        ]);

        $otherTransaction = Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => Transaction::STATUS_COMPLETED,
            'total_amount' => 10000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.order_number', '26032301CATSYR')
            ->assertJsonPath('data.store_name', 'Toko Segar Jaya')
            ->assertJsonPath('data.status', Transaction::STATUS_PROCESSING)
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_PROCESSING)
            ->assertJsonPath('data.status_label', 'Sedang Diproses')
            ->assertJsonPath('data.total_amount', 9999999)
            ->assertJsonPath('data.total_amount_label', 'Rp. 9.999.999')
            ->assertJsonPath('data.total_items', 3)
            ->assertJsonPath('data.items.0.tenant_name', 'Toko Segar Jaya')
            ->assertJsonPath('data.items.0.product_name', 'Bayam Hijau')
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.items.0.unit_price', 5000)
            ->assertJsonPath('data.items.0.unit_price_label', 'Rp. 5.000')
            ->assertJsonPath('data.items.0.line_total', 15000)
            ->assertJsonPath('data.items.0.line_total_label', 'Rp. 15.000')
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.pickup_scheduled_at', '15:00')
            ->assertJsonPath('data.payment_method', 'Transfer Bank')
            ->assertJsonPath('data.status_timelines.0.status_code', Transaction::STATUS_CODE_PENDING_PAYMENT)
            ->assertJsonPath('data.status_timelines.0.title', 'Pembayaran Transfer Bank Lunas')
            ->assertJsonPath('data.status_timelines.1.title', 'Pesanan diterima')
            ->assertJsonPath('data.status_timelines.2.title', 'Pesanan sedang diproses');

        $this->assertCount(3, $response->json('data.status_timelines'));
        $this->assertNotSame($otherTransaction->id, $response->json('data.id'));
    }

    public function test_transaction_detail_returns_not_found_for_other_users_transaction(): void
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

        $otherUser = User::query()->create([
            'name' => 'Siti',
            'email' => 'siti@example.com',
            'phone' => '+628111111111',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-detail-forbidden-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => Transaction::STATUS_CANCELED,
            'total_amount' => 20000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_VIRTUAL_ACCOUNT,
            'transaction_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    public function test_transaction_detail_formats_remaining_status_labels_correctly(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi2@example.com',
            'phone' => '+6281333333333',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-detail-labels-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $onTheWayTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'ONTHEWAY001',
            'status' => Transaction::STATUS_ON_THE_WAY,
            'total_amount' => 15000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        $completedTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETED001',
            'status' => Transaction::STATUS_COMPLETED,
            'total_amount' => 16000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        $canceledTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'CANCELED001',
            'status' => Transaction::STATUS_CANCELED,
            'total_amount' => 17000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$onTheWayTransaction->id)
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Dalam Perjalanan');

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$completedTransaction->id)
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Pesanan Selesai');

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$canceledTransaction->id)
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Pesanan Dibatalkan');
    }

    public function test_buyer_can_complete_own_transaction_that_is_on_the_way(): void
    {
        [$user, $plainTextToken] = $this->createAuthenticatedUser('buyer-complete-token');
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETE001',
            'status' => Transaction::STATUS_ON_THE_WAY,
            'transaction_at' => now(),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_ON_THE_WAY,
            'title' => 'Pesanan dalam perjalanan',
            'description' => 'Pesanan sedang dikirim',
            'sequence' => 1,
            'status_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson('/api/users/transactions/'.$transaction->id.'/complete')
            ->assertOk()
            ->assertJsonPath('message', 'Pesanan berhasil diselesaikan.')
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.status', Transaction::STATUS_COMPLETED)
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED)
            ->assertJsonPath('data.status_label', 'Pesanan Selesai');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_COMPLETED,
            'description' => 'Pesanan telah diterima dan diselesaikan oleh buyer',
            'sequence' => 2,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('data.can_complete', false);
    }

    public function test_buyer_can_complete_own_transaction_that_is_ready_for_pickup(): void
    {
        [$user, $plainTextToken] = $this->createAuthenticatedUser('buyer-complete-pickup-token');
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETE002',
            'status' => Transaction::STATUS_READY_FOR_PICKUP,
            'transaction_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('data.can_complete', true);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson('/api/users/transactions/'.$transaction->id.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED);
    }

    public function test_completing_an_already_completed_transaction_is_idempotent(): void
    {
        [$user, $plainTextToken] = $this->createAuthenticatedUser('buyer-complete-idempotent-token');
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETE003',
            'status' => Transaction::STATUS_ON_THE_WAY,
            'transaction_at' => now(),
        ]);

        $endpoint = '/api/users/transactions/'.$transaction->id.'/complete';

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson($endpoint)
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson($endpoint)
            ->assertOk()
            ->assertJsonPath('message', 'Pesanan sudah selesai.')
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED);

        $this->assertSame(1, $transaction->statusHistories()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->count());
    }

    public function test_buyer_cannot_complete_transaction_from_an_invalid_status(): void
    {
        [$user, $plainTextToken] = $this->createAuthenticatedUser('buyer-complete-invalid-token');
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETE004',
            'status' => Transaction::STATUS_PROCESSING,
            'transaction_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson('/api/users/transactions/'.$transaction->id.'/complete')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(Transaction::STATUS_PROCESSING, $transaction->fresh()->status);
    }

    public function test_buyer_cannot_complete_another_buyers_transaction(): void
    {
        [, $plainTextToken] = $this->createAuthenticatedUser('buyer-complete-owner-token');
        $otherUser = User::query()->create([
            'name' => 'Buyer Lain',
            'email' => 'buyer-complete-other@example.com',
            'phone' => '+6281888888888',
            'type' => 'phone',
        ]);
        $transaction = Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'COMPLETE005',
            'status' => Transaction::STATUS_ON_THE_WAY,
            'transaction_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson('/api/users/transactions/'.$transaction->id.'/complete')
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    public function test_complete_transaction_endpoint_requires_buyer_authentication_and_role(): void
    {
        [$user, $plainTextToken] = $this->createAuthenticatedUser('seller-complete-buyer-endpoint-token');
        $user->forceFill(['role' => User::ROLE_SELLER])->save();
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => 'COMPLETE006',
            'status' => Transaction::STATUS_ON_THE_WAY,
            'transaction_at' => now(),
        ]);
        $endpoint = '/api/users/transactions/'.$transaction->id.'/complete';

        $this->patchJson($endpoint)
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->patchJson($endpoint)
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role buyer.');
    }

    private function createAuthenticatedUser(string $plainTextToken): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => $plainTextToken.'@example.com',
            'phone' => '+6281999999999',
            'type' => 'phone',
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
