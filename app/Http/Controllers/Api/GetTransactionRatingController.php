<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\TransactionRatingResponseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetTransactionRatingController extends Controller
{
    public function __invoke(Request $request, string $transactionId): JsonResponse
    {
        $transaction = Transaction::query()
            ->whereKey($transactionId)
            ->where('user_id', $request->user()->id)
            ->with('rating')
            ->first();

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $transaction->rating) {
            return response()->json([
                'message' => 'Rating pesanan belum tersedia.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Rating pesanan berhasil diambil.',
            'data' => TransactionRatingResponseMapper::map($transaction->rating),
        ]);
    }
}
