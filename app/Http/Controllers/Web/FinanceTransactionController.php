<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(Request $request, FinanceDisbursementSyncer $syncer): View
    {
        Transaction::query()
            ->with('items.tenant')
            ->whereDoesntHave('financeDisbursements')
            ->chunkById(100, fn ($transactions) => $transactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction)));

        $status = $request->query('status');
        $disbursements = FinanceTransactionDisbursement::query()
            ->with(['transaction.user', 'tenant.owner'])
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('finance.transactions.index', [
            'disbursements' => $disbursements,
            'selectedStatus' => $status,
            'statuses' => [
                FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT => 'Menunggu Pembayaran Buyer',
                FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED => 'Pembayaran Buyer Valid',
                FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER => 'Dana Masuk ke Seller',
            ],
        ]);
    }

    public function show(string $id, FinanceDisbursementSyncer $syncer): View
    {
        $transaction = Transaction::query()
            ->with(['items.tenant.owner', 'user', 'statusHistories', 'financeDisbursements.tenant.owner'])
            ->findOrFail($id);

        $syncer->syncForTransaction($transaction);
        $transaction->load('financeDisbursements.tenant.owner');

        return view('finance.transactions.show', [
            'transaction' => $transaction,
        ]);
    }

    public function confirmBuyerPayment(Request $request, string $id, FinanceDisbursementSyncer $syncer): RedirectResponse
    {
        $transaction = Transaction::query()->with('items.tenant')->findOrFail($id);

        DB::transaction(function () use ($request, $transaction, $syncer): void {
            $transaction->forceFill([
                'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
            ])->save();

            TransactionStatusHistory::query()->create([
                'transaction_id' => $transaction->id,
                'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
                'title' => 'Pembayaran buyer dikonfirmasi',
                'description' => 'Finance mengonfirmasi pembayaran buyer dan transaksi masuk ke seller.',
                'sequence' => ((int) $transaction->statusHistories()->max('sequence')) + 1,
                'status_at' => now(),
            ]);

            $syncer->syncForTransaction($transaction)->each(function (FinanceTransactionDisbursement $disbursement) use ($request): void {
                if ($disbursement->status === FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER) {
                    return;
                }

                $disbursement->forceFill([
                    'status' => FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED,
                    'buyer_payment_confirmed_at' => $disbursement->buyer_payment_confirmed_at ?? now(),
                    'confirmed_by_user_id' => $request->user()->id,
                ])->save();
            });
        });

        return back()->with('status', 'Pembayaran buyer berhasil dikonfirmasi. Transaksi sudah masuk ke seller.');
    }

    public function disburseToSeller(Request $request, string $id): RedirectResponse
    {
        $disbursement = FinanceTransactionDisbursement::query()->findOrFail($id);

        if ($disbursement->status === FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT) {
            return back()->withErrors(['disbursement' => 'Konfirmasi pembayaran buyer terlebih dahulu.']);
        }

        $disbursement->forceFill([
            'status' => FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER,
            'disbursed_at' => now(),
            'disbursed_by_user_id' => $request->user()->id,
        ])->save();

        return back()->with('status', 'Dana transaksi berhasil disalurkan ke seller.');
    }
}
