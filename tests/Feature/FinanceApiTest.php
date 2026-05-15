<?php

namespace Tests\Feature;

use App\Models\FinanceTransactionDisbursement;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_view_dashboard_and_transaction_disbursement_table(): void
    {
        [, $token] = $this->createAuthenticatedUser('finance@example.com', '+6281400000001', 'finance-token', User::ROLE_FINANCE);
        $transaction = $this->createTransactionForStore('FIN001', 150000);

        $dashboardResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/dashboard');

        $dashboardResponse
            ->assertOk()
            ->assertJsonPath('data.total_transactions', 1)
            ->assertJsonPath('data.total_transaction_amount', 150000)
            ->assertJsonPath('data.active_store_count', 1)
            ->assertJsonPath('data.recent_transactions.0.order_number', 'FIN001');

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/transactions');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.unique_code', 'FIN-FIN001-'.str_pad((string) $transaction->items()->first()->tenant_id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.0.amount', 150000)
            ->assertJsonPath('data.0.status', FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT);
    }

    public function test_finance_can_confirm_buyer_payment_and_disburse_to_seller(): void
    {
        [, $token] = $this->createAuthenticatedUser('finance-flow@example.com', '+6281400000002', 'finance-flow-token', User::ROLE_FINANCE);
        $transaction = $this->createTransactionForStore('FIN002', 100000);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/finance/transactions/'.$transaction->id.'/confirm-buyer-payment')
            ->assertOk()
            ->assertJsonPath('message', 'Pembayaran buyer berhasil dikonfirmasi dan transaksi masuk ke seller.');

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_ACCEPTED_BY_STORE, $transaction->status);

        $disbursement = FinanceTransactionDisbursement::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED, $disbursement->status);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/finance/disbursements/'.$disbursement->id.'/disburse-to-seller')
            ->assertOk()
            ->assertJsonPath('data.status', FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER);

        $this->assertDatabaseHas('finance_transaction_disbursements', [
            'id' => $disbursement->id,
            'status' => FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER,
        ]);
    }

    public function test_seller_cannot_access_finance_endpoints(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-finance@example.com', '+6281400000003', 'seller-finance-token', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role finance.');
    }

    private function createTransactionForStore(string $orderNumber, int $lineTotal): Transaction
    {
        [$seller] = $this->createAuthenticatedUser('seller-'.strtolower($orderNumber).'@example.com', '+628149'.substr(preg_replace('/\D/', '', $orderNumber), -4), 'seller-'.$orderNumber, User::ROLE_SELLER);

        $buyer = User::query()->create([
            'name' => 'Buyer '.$orderNumber,
            'email' => 'buyer-'.strtolower($orderNumber).'@example.com',
            'phone' => '+628148'.substr(preg_replace('/\D/', '', $orderNumber), -4),
            'type' => User::AUTH_TYPE_PHONE,
            'role' => User::ROLE_BUYER,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Toko '.$orderNumber,
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_GROCERIES,
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
            'status' => Transaction::STATUS_PENDING_PAYMENT,
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
