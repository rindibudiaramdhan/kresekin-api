<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CompleteBuyerTransactionController extends Controller
{
    public function __invoke(Request $request, string $transactionId): JsonResponse
    {
        [$transaction, $wasCompleted] = DB::transaction(function () use ($request, $transactionId): array {
            $transaction = Transaction::query()
                ->whereKey($transactionId)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return [null, false];
            }

            if ($transaction->statusCode() === Transaction::STATUS_CODE_COMPLETED) {
                return [$transaction, false];
            }

            if (! $transaction->canBeCompletedByBuyer()) {
                throw ValidationException::withMessages([
                    'status' => ['Pesanan hanya dapat diselesaikan saat sedang dalam perjalanan atau siap diambil.'],
                ]);
            }

            $sequence = ((int) TransactionStatusHistory::query()
                ->where('transaction_id', $transaction->id)
                ->max('sequence')) + 1;

            $transaction->forceFill([
                'status' => Transaction::STATUS_COMPLETED,
            ])->save();

            TransactionStatusHistory::query()->create([
                'transaction_id' => $transaction->id,
                'status' => Transaction::STATUS_COMPLETED,
                'title' => 'Pesanan selesai',
                'description' => 'Pesanan telah diterima dan diselesaikan oleh buyer',
                'sequence' => $sequence,
                'status_at' => now(),
            ]);

            return [$transaction->fresh(), true];
        });

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => $wasCompleted
                ? 'Pesanan berhasil diselesaikan.'
                : 'Pesanan sudah selesai.',
            'data' => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
                'status_label' => 'Pesanan Selesai',
            ],
        ]);
    }
}
