<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data' => $transactions->getCollection()->map(fn ($transaction) => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
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
}
