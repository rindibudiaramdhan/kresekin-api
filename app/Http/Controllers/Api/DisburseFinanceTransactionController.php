<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisburseFinanceTransactionController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $disbursement = FinanceTransactionDisbursement::query()
            ->with(['transaction', 'tenant.owner'])
            ->find($id);

        if (! $disbursement) {
            return response()->json([
                'message' => 'Data penyaluran dana tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($disbursement->status === FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT) {
            return response()->json([
                'message' => 'Konfirmasi pembayaran buyer terlebih dahulu sebelum dana disalurkan ke seller.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $disbursement->forceFill([
            'status' => FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER,
            'disbursed_at' => now(),
            'disbursed_by_user_id' => $request->user()->id,
        ])->save();

        return response()->json([
            'message' => 'Dana transaksi berhasil disalurkan ke seller.',
            'data' => [
                'id' => $disbursement->id,
                'unique_code' => $disbursement->unique_code,
                'status' => $disbursement->status,
                'amount' => $disbursement->amount,
                'amount_label' => 'Rp. '.number_format($disbursement->amount, 0, ',', '.'),
                'store_name' => $disbursement->tenant?->name,
                'seller_name' => $disbursement->seller?->name,
                'disbursed_at' => $disbursement->disbursed_at?->toIso8601String(),
            ],
        ]);
    }
}
