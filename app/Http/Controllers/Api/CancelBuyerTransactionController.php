<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBuyerTransactionRequest;
use App\Models\CancellationReasonCategory;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CancelBuyerTransactionController extends Controller
{
    public function __invoke(CancelBuyerTransactionRequest $request, string $transactionId): JsonResponse
    {
        $validated = $request->validated();

        [$transaction, $wasCanceled] = DB::transaction(function () use ($request, $transactionId, $validated): array {
            $transaction = Transaction::query()
                ->whereKey($transactionId)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return [null, false];
            }

            if ($transaction->statusCode() === Transaction::STATUS_CODE_CANCELED) {
                return [$transaction->load('cancellationReasonCategory'), false];
            }

            if (! $transaction->canBeCanceledByBuyer()) {
                throw ValidationException::withMessages([
                    'status' => ['Pesanan yang sudah selesai tidak dapat dibatalkan.'],
                ]);
            }

            $category = CancellationReasonCategory::query()
                ->where('is_active', true)
                ->findOrFail($validated['cancellation_reason_category_id']);
            $reasonText = $validated['cancellation_reason_text'] ?? null;
            $sequence = ((int) TransactionStatusHistory::query()
                ->where('transaction_id', $transaction->id)
                ->max('sequence')) + 1;

            $transaction->forceFill([
                'status' => Transaction::STATUS_CANCELED,
                'cancellation_reason_category_id' => $category->id,
                'cancellation_reason_text' => $reasonText,
            ])->save();

            TransactionStatusHistory::query()->create([
                'transaction_id' => $transaction->id,
                'status' => Transaction::STATUS_CANCELED,
                'title' => 'Pesanan dibatalkan',
                'description' => sprintf(
                    'Pesanan dibatalkan oleh buyer. Alasan: %s%s',
                    $category->name,
                    $reasonText ? ' - '.$reasonText : '',
                ),
                'sequence' => $sequence,
                'status_at' => now(),
            ]);

            return [$transaction->fresh()->setRelation('cancellationReasonCategory', $category), true];
        });

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => $wasCanceled
                ? 'Pesanan berhasil dibatalkan.'
                : 'Pesanan sudah dibatalkan.',
            'data' => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
                'status_label' => 'Pesanan Dibatalkan',
                'cancellation_reason' => [
                    'category_id' => $transaction->cancellation_reason_category_id,
                    'category_name' => $transaction->cancellationReasonCategory?->name,
                    'allows_free_text' => $transaction->cancellationReasonCategory?->allows_free_text,
                    'reason_text' => $transaction->cancellation_reason_text,
                ],
            ],
        ]);
    }
}
