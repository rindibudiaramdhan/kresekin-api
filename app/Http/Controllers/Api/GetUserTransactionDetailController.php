<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetUserTransactionDetailController extends Controller
{
    public function __invoke(Request $request, int $transactionId): JsonResponse
    {
        $transaction = $request->user()
            ->transactions()
            ->with(['items.tenant', 'statusHistories'])
            ->find($transactionId);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Detail riwayat transaksi berhasil diambil.',
            'data' => [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'store_name' => $transaction->items->first()?->tenant?->name,
                'status' => $transaction->status,
                'status_code' => $transaction->statusCode(),
                'status_label' => $this->formatStatusLabel($transaction->status),
                'total_amount' => $transaction->total_amount,
                'total_amount_label' => $this->moneyLabel((int) $transaction->total_amount),
                'total_items' => $transaction->items->sum('quantity'),
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
                'delivery_method' => $transaction->delivery_method,
                'pickup_scheduled_at' => $transaction->pickup_scheduled_at,
                'payment_method' => $transaction->payment_method,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
                'status_timelines' => $transaction->statusHistories->map(fn ($history) => [
                    'id' => $history->id,
                    'status' => $history->status,
                    'status_code' => $this->statusCode($history->status),
                    'title' => $history->title,
                    'description' => $history->description,
                    'time' => $history->status_at?->timezone('Asia/Jakarta')->format('H:i'),
                    'time_label' => $history->status_at?->timezone('Asia/Jakarta')->format('H:i'),
                    'is_completed' => true,
                    'sequence' => $history->sequence,
                ])->values(),
            ],
        ]);
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Diterima Toko',
            Transaction::STATUS_PROCESSING => 'Sedang Diproses',
            Transaction::STATUS_ON_THE_WAY => 'Dalam Perjalanan',
            Transaction::STATUS_COMPLETED => 'Pesanan Selesai',
            Transaction::STATUS_CANCELED => 'Pesanan Dibatalkan',
            default => ucfirst($status),
        };
    }

    private function statusCode(string $status): ?string
    {
        $normalizedStatus = strtolower($status);

        foreach (Transaction::statusMap() as $code => $mappedStatus) {
            if ($normalizedStatus === strtolower($mappedStatus)) {
                return $code;
            }
        }

        return null;
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
