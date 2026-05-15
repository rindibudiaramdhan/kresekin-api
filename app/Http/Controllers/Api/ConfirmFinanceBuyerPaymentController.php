<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ConfirmFinanceBuyerPaymentController extends Controller
{
    public function __invoke(Request $request, int $id, FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $transaction = Transaction::query()->with('items.tenant')->find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($request, $transaction, $syncer): void {
            $transaction->forceFill([
                'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
            ])->save();

            $sequence = ((int) $transaction->statusHistories()->max('sequence')) + 1;

            TransactionStatusHistory::query()->create([
                'transaction_id' => $transaction->id,
                'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
                'title' => 'Pembayaran buyer dikonfirmasi',
                'description' => 'Finance mengonfirmasi pembayaran buyer dan transaksi masuk ke seller.',
                'sequence' => $sequence,
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

        return response()->json([
            'message' => 'Pembayaran buyer berhasil dikonfirmasi dan transaksi masuk ke seller.',
        ]);
    }
}
