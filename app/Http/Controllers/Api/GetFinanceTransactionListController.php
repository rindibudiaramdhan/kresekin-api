<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GetFinanceTransactionListController extends Controller
{
    public function __invoke(Request $request, FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT,
                FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED,
                FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER,
            ])],
        ]);

        Transaction::query()
            ->with('items.tenant')
            ->whereDoesntHave('financeDisbursements')
            ->chunkById(100, fn ($transactions) => $transactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction)));

        $disbursements = FinanceTransactionDisbursement::query()
            ->with(['transaction.user', 'tenant.owner'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar transaksi finance berhasil diambil.',
            'data' => $disbursements->getCollection()
                ->map(fn (FinanceTransactionDisbursement $disbursement): array => $this->mapDisbursement($disbursement))
                ->values(),
            'meta' => [
                'current_page' => $disbursements->currentPage(),
                'per_page' => $disbursements->perPage(),
                'last_page' => $disbursements->lastPage(),
                'total' => $disbursements->total(),
                'from' => $disbursements->firstItem(),
                'to' => $disbursements->lastItem(),
            ],
            'links' => [
                'first' => $disbursements->url(1),
                'last' => $disbursements->url($disbursements->lastPage()),
                'prev' => $disbursements->previousPageUrl(),
                'next' => $disbursements->nextPageUrl(),
            ],
        ]);
    }

    private function mapDisbursement(FinanceTransactionDisbursement $disbursement): array
    {
        return [
            'id' => $disbursement->id,
            'unique_code' => $disbursement->unique_code,
            'status' => $disbursement->status,
            'amount' => $disbursement->amount,
            'amount_label' => $this->moneyLabel($disbursement->amount),
            'store' => [
                'id' => $disbursement->tenant?->id,
                'name' => $disbursement->tenant?->name,
            ],
            'seller' => [
                'id' => $disbursement->seller?->id,
                'name' => $disbursement->seller?->name,
                'email' => $disbursement->seller?->email,
                'phone' => $disbursement->seller?->phone,
            ],
            'transaction' => [
                'id' => $disbursement->transaction?->id,
                'order_number' => $disbursement->transaction?->order_number,
                'status' => $disbursement->transaction?->status,
                'status_code' => $disbursement->transaction?->statusCode(),
                'total_amount' => $disbursement->transaction?->total_amount,
                'total_amount_label' => $this->moneyLabel((int) $disbursement->transaction?->total_amount),
            ],
            'buyer_payment_confirmed_at' => $disbursement->buyer_payment_confirmed_at?->toIso8601String(),
            'disbursed_at' => $disbursement->disbursed_at?->toIso8601String(),
        ];
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
