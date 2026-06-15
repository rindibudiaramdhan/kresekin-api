<?php

namespace Tests\Feature;

use App\Models\AgentCommissionWithdrawal;
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

    public function test_finance_can_view_agent_commission_withdrawal_summary_and_filtered_list(): void
    {
        [, $token] = $this->createAuthenticatedUser('finance-withdrawals@example.com', '+6281400000004', 'finance-withdrawals-token', User::ROLE_FINANCE);
        [$agentSanti] = $this->createAuthenticatedUser('agent-santi@example.com', '+6281400000005', 'agent-santi-token', User::ROLE_AGENT);
        [$agentDenny] = $this->createAuthenticatedUser('agent-denny@example.com', '+6281400000006', 'agent-denny-token', User::ROLE_AGENT);

        $agentSanti->forceFill([
            'name' => 'Santi',
            'agent_code' => 'KA-10001',
            'bank_name' => 'Mandiri',
            'bank_account_name' => 'Santi',
            'bank_account_number' => '1240098999',
        ])->save();
        $agentDenny->forceFill([
            'name' => 'Denny',
            'agent_code' => 'KA-10002',
            'bank_name' => 'BSI',
            'bank_account_name' => 'Denny',
            'bank_account_number' => '012322777',
        ])->save();

        $requested = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agentSanti->id,
            'amount' => 250000,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'requested_at' => now()->setDate(2026, 6, 10),
        ]);
        AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agentDenny->id,
            'amount' => 500000,
            'status' => AgentCommissionWithdrawal::STATUS_APPROVED,
            'requested_at' => now()->setDate(2026, 6, 11),
        ]);
        AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agentDenny->id,
            'amount' => 750000,
            'status' => AgentCommissionWithdrawal::STATUS_PAID,
            'requested_at' => now()->setDate(2026, 6, 12),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/commission-withdrawals/summary')
            ->assertOk()
            ->assertJsonPath('data.total_disbursed', 750000)
            ->assertJsonPath('data.total_disbursed_label', 'Rp 750.000')
            ->assertJsonPath('data.total_pending', 750000)
            ->assertJsonPath('data.total_withdrawals', 3);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/finance/commission-withdrawals?search=Santi&status=requested&date_from=2026-06-01&date_to=2026-06-30')
            ->assertOk()
            ->assertJsonPath('data.0.id', $requested->id)
            ->assertJsonPath('data.0.agent.name', 'Santi')
            ->assertJsonPath('data.0.bank.name', 'Mandiri')
            ->assertJsonPath('data.0.bank.account_number_masked', '1240098xxx')
            ->assertJsonPath('data.0.amount_label', 'Rp 250.000')
            ->assertJsonPath('data.0.status', AgentCommissionWithdrawal::STATUS_REQUESTED)
            ->assertJsonPath('data.0.status_label', 'Pengajuan')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_finance_can_approve_reject_and_mark_agent_commission_withdrawals_as_paid(): void
    {
        [$finance, $token] = $this->createAuthenticatedUser('finance-actions@example.com', '+6281400000007', 'finance-actions-token', User::ROLE_FINANCE);
        [$agent] = $this->createAuthenticatedUser('agent-actions@example.com', '+6281400000008', 'agent-actions-token', User::ROLE_AGENT);

        $agent->forceFill([
            'name' => 'Agent Action',
            'bank_name' => 'BCA',
            'bank_account_name' => 'Agent Action',
            'bank_account_number' => '1234567890',
        ])->save();

        $approvedWithdrawal = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agent->id,
            'amount' => 100000,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);
        $rejectedWithdrawal = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agent->id,
            'amount' => 200000,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/finance/commission-withdrawals/'.$approvedWithdrawal->id.'/approve')
            ->assertOk()
            ->assertJsonPath('message', 'Pengajuan pencairan dana berhasil disetujui.')
            ->assertJsonPath('data.status', AgentCommissionWithdrawal::STATUS_APPROVED)
            ->assertJsonPath('data.status_label', 'Diproses')
            ->assertJsonPath('data.processed_by.id', $finance->id);

        $this->assertDatabaseHas('agent_commission_withdrawals', [
            'id' => $approvedWithdrawal->id,
            'status' => AgentCommissionWithdrawal::STATUS_APPROVED,
            'approved_by_user_id' => $finance->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/finance/commission-withdrawals/'.$approvedWithdrawal->id.'/mark-as-paid')
            ->assertOk()
            ->assertJsonPath('message', 'Pencairan dana berhasil diselesaikan.')
            ->assertJsonPath('data.status', AgentCommissionWithdrawal::STATUS_PAID);

        $this->assertDatabaseHas('agent_commission_withdrawals', [
            'id' => $approvedWithdrawal->id,
            'status' => AgentCommissionWithdrawal::STATUS_PAID,
            'paid_by_user_id' => $finance->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/finance/commission-withdrawals/'.$rejectedWithdrawal->id.'/reject', [
                'reason' => AgentCommissionWithdrawal::REJECTION_INVALID_ACCOUNT,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Pengajuan pencairan dana berhasil ditolak.')
            ->assertJsonPath('data.status', AgentCommissionWithdrawal::STATUS_REJECTED)
            ->assertJsonPath('data.rejection.reason', AgentCommissionWithdrawal::REJECTION_INVALID_ACCOUNT)
            ->assertJsonPath('data.rejection.reason_label', 'Data rekening tidak valid')
            ->assertJsonPath('data.rejection.rejected_by.id', $finance->id);
    }

    public function test_finance_commission_withdrawal_mutations_validate_status_reason_and_role(): void
    {
        [, $financeToken] = $this->createAuthenticatedUser('finance-validation@example.com', '+6281400000009', 'finance-validation-token', User::ROLE_FINANCE);
        [$agent, $agentToken] = $this->createAuthenticatedUser('agent-validation@example.com', '+6281400000010', 'agent-validation-token', User::ROLE_AGENT);

        $withdrawal = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agent->id,
            'amount' => 100000,
            'status' => AgentCommissionWithdrawal::STATUS_PAID,
            'requested_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$agentToken)
            ->getJson('/api/finance/commission-withdrawals')
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$financeToken)
            ->patchJson('/api/finance/commission-withdrawals/'.$withdrawal->id.'/approve')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Hanya pengajuan pencairan yang dapat disetujui.');

        $pendingWithdrawal = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $agent->id,
            'amount' => 120000,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$financeToken)
            ->patchJson('/api/finance/commission-withdrawals/'.$pendingWithdrawal->id.'/reject', [
                'reason' => 'unknown_reason',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->withHeader('Authorization', 'Bearer '.$financeToken)
            ->patchJson('/api/finance/commission-withdrawals/'.$pendingWithdrawal->id.'/mark-as-paid')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Hanya pencairan yang sedang diproses yang dapat diselesaikan.');
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
