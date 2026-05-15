<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetFinanceTransactionDetailController extends Controller
{
    public function __invoke(int $id, FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $transaction = Transaction::query()
            ->with(['items.tenant.owner', 'user', 'statusHistories', 'financeDisbursements.tenant.owner'])
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $syncer->syncForTransaction($transaction);
        $transaction->load('financeDisbursements.tenant.owner');

        return response()->json([
            'message' => 'Detail transaksi finance berhasil diambil.',
            'data' => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'buyer' => [
                    'id' => $transaction->user?->id,
                    'name' => $transaction->user?->name,
                    'email' => $transaction->user?->email,
                    'phone' => $transaction->user?->phone,
                ],
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
                'subtotal_amount' => $transaction->subtotal_amount,
                'subtotal_amount_label' => $this->moneyLabel((int) $transaction->subtotal_amount),
                'delivery_fee' => $transaction->delivery_fee,
                'delivery_fee_label' => $this->moneyLabel((int) $transaction->delivery_fee),
                'discount_amount' => $transaction->discount_amount,
                'discount_amount_label' => $this->moneyLabel((int) $transaction->discount_amount),
                'total_amount' => $transaction->total_amount,
                'total_amount_label' => $this->moneyLabel((int) $transaction->total_amount),
                'payment_method' => $transaction->payment_method,
                'payment_method_option_name' => $transaction->payment_method_option_name,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
                'items' => $transaction->items->map(fn (TransactionItem $item): array => [
                    'id' => $item->id,
                    'tenant_id' => $item->tenant_id,
                    'tenant_name' => $item->tenant?->name,
                    'seller_name' => $item->tenant?->owner?->name,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit_price_label' => $this->moneyLabel($item->unit_price),
                    'line_total' => $item->line_total,
                    'line_total_label' => $this->moneyLabel($item->line_total),
                ])->values(),
                'disbursements' => $transaction->financeDisbursements
                    ->map(fn (FinanceTransactionDisbursement $disbursement): array => [
                        'id' => $disbursement->id,
                        'unique_code' => $disbursement->unique_code,
                        'status' => $disbursement->status,
                        'amount' => $disbursement->amount,
                        'amount_label' => $this->moneyLabel($disbursement->amount),
                        'store_name' => $disbursement->tenant?->name,
                        'seller_name' => $disbursement->seller?->name,
                        'buyer_payment_confirmed_at' => $disbursement->buyer_payment_confirmed_at?->toIso8601String(),
                        'disbursed_at' => $disbursement->disbursed_at?->toIso8601String(),
                    ])
                    ->values(),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
