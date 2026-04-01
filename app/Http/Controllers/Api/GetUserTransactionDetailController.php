<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetUserTransactionDetailController extends Controller
{
    public function __invoke(Request $request, int $transactionId): JsonResponse
    {
        $transaction = $request->user()
            ->transactions()
            ->with('statusHistories')
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
                'status' => $transaction->status,
                'status_label' => $this->formatStatusLabel($transaction->status),
                'total_amount' => $transaction->total_amount,
                'total_amount_label' => 'Rp. '.number_format((int) $transaction->total_amount, 0, ',', '.'),
                'delivery_method' => $transaction->delivery_method,
                'payment_method' => $transaction->payment_method,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
                'status_timelines' => $transaction->statusHistories->map(fn ($history) => [
                    'id' => $history->id,
                    'status' => $history->status,
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
}
