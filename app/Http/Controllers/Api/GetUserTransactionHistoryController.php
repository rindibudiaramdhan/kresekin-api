<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GetUserTransactionHistoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_code' => ['nullable', 'string', Rule::in(Transaction::statusCodes())],
        ]);

        $status = Transaction::statusFromCode($validated['status_code'] ?? null);

        $transactions = $request->user()
            ->transactions()
            ->with(['items.tenant', 'cancellationReasonCategory'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data' => $transactions->getCollection()->map(fn ($transaction) => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'store_name' => $transaction->items->first()?->tenant?->name,
                'total_items' => $transaction->items->sum('quantity'),
                'delivery_method' => $transaction->delivery_method,
                'delivery_method_code' => $transaction->delivery_method_code,
                'delivery_fee' => $transaction->delivery_fee,
                'delivery_fee_label' => $this->moneyLabel((int) $transaction->delivery_fee),
                'items' => $transaction->items->map(fn (TransactionItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'tenant_id' => $item->tenant_id,
                    'tenant_name' => $item->tenant?->name,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit_price_label' => $this->moneyLabel($item->unit_price),
                    'line_total' => $item->line_total,
                    'line_total_label' => $this->moneyLabel($item->line_total),
                ])->values(),
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
                'cancellation_reason' => $transaction->statusCode() === Transaction::STATUS_CODE_CANCELED ? [
                    'category_id' => $transaction->cancellation_reason_category_id,
                    'category_name' => $transaction->cancellationReasonCategory?->name,
                    'allows_free_text' => $transaction->cancellationReasonCategory?->allows_free_text,
                    'reason_text' => $transaction->cancellation_reason_text,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
            'links' => [
                'first' => $transactions->url(1),
                'last' => $transactions->url($transactions->lastPage()),
                'prev' => $transactions->previousPageUrl(),
                'next' => $transactions->nextPageUrl(),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
