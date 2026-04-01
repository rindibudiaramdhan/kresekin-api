<?php

namespace Tests\Feature;

use App\Models\Transaction;
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

        foreach (range(1, 12) as $index) {
            Transaction::query()->create([
                'user_id' => $user->id,
                'order_number' => sprintf('TRX%04d', $index),
                'status' => 'Pesanan Selesai',
                'transaction_at' => now()->subMinutes(12 - $index),
            ]);
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
            ->assertJsonPath('data.1.order_number', 'TRX0011')
            ->assertJsonPath('data.9.order_number', 'TRX0003');

        $this->assertCount(10, $response->json('data'));
    }

    public function test_transaction_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/users/transactions');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
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
            'payment_method' => Transaction::PAYMENT_METHOD_BANK_TRANSFER,
            'transaction_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 0),
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
            ->assertJsonPath('data.status', Transaction::STATUS_PROCESSING)
            ->assertJsonPath('data.status_label', 'Sedang Diproses')
            ->assertJsonPath('data.total_amount', 9999999)
            ->assertJsonPath('data.total_amount_label', 'Rp. 9.999.999')
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.payment_method', 'Transfer Bank')
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
}
