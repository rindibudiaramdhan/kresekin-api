<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRatingRequest;
use App\Models\Transaction;
use App\Models\TransactionRating;
use App\Support\TransactionRatingResponseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class StoreTransactionRatingController extends Controller
{
    public function __invoke(StoreTransactionRatingRequest $request, string $transactionId): JsonResponse
    {
        $rating = DB::transaction(function () use ($request, $transactionId): ?TransactionRating {
            $transaction = Transaction::query()
                ->whereKey($transactionId)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return null;
            }

            if ($transaction->statusCode() !== Transaction::STATUS_CODE_COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => ['Rating hanya dapat diberikan setelah pesanan selesai.'],
                ]);
            }

            if ($transaction->rating()->exists()) {
                throw ValidationException::withMessages([
                    'rating' => ['Pesanan ini sudah pernah diberi rating.'],
                ]);
            }

            return $transaction->rating()->create([
                'user_id' => $request->user()->id,
                ...$request->validated(),
            ]);
        });

        if (! $rating) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Rating pesanan berhasil disimpan.',
            'data' => TransactionRatingResponseMapper::map($rating),
        ], Response::HTTP_CREATED);
    }
}
