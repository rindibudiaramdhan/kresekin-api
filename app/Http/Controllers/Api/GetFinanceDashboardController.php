<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\JsonResponse;

class GetFinanceDashboardController extends Controller
{
    public function __invoke(FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $recentTransactions = Transaction::query()
            ->with(['items.tenant.owner', 'user'])
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $recentTransactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction));

        return response()->json([
            'message' => 'Dashboard finance berhasil diambil.',
            'data' => [
                'total_transactions' => Transaction::query()->count(),
                'total_transaction_amount' => (int) Transaction::query()->sum('total_amount'),
                'total_transaction_amount_label' => $this->moneyLabel((int) Transaction::query()->sum('total_amount')),
                'active_store_count' => Tenant::query()
                    ->whereHas('products')
                    ->count(),
                'all_store_count' => Tenant::query()->count(),
                'recent_transactions' => $recentTransactions
                    ->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction))
                    ->values(),
            ],
        ]);
    }

    private function mapTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'order_number' => $transaction->order_number,
            'buyer' => [
                'id' => $transaction->user?->id,
                'name' => $transaction->user?->name,
                'email' => $transaction->user?->email,
                'phone' => $transaction->user?->phone,
            ],
            'store_names' => $transaction->items->pluck('tenant.name')->filter()->unique()->values(),
            'total_amount' => $transaction->total_amount,
            'total_amount_label' => $this->moneyLabel((int) $transaction->total_amount),
            'status' => $transaction->status,
            'status_code' => $transaction->statusCode(),
            'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
