<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetSellerOrderDetailController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $sellerId = $request->user()->id;

        $order = Transaction::query()
            ->with(['items.tenant', 'statusHistories', 'user', 'cancellationReasonCategory'])
            ->where('id', $id)
            ->whereHas('items.tenant', fn ($query) => $query->where('owner_user_id', $sellerId))
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Detail order seller berhasil diambil.',
            'data' => $this->mapOrder($order, $sellerId),
        ]);
    }

    private function mapOrder(Transaction $order, string $sellerId): array
    {
        $items = $this->sellerItems($order, $sellerId);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'buyer' => [
                'id' => $order->user?->id,
                'name' => $order->user?->name,
                'email' => $order->user?->email,
                'phone' => $order->user?->phone,
            ],
            'store_name' => $items->first()?->tenant?->name,
            'status' => $order->status,
            'status_code' => $order->statusCode(),
            'status_label' => $this->formatStatusLabel($order->status),
            'cancellation_reason' => $order->statusCode() === Transaction::STATUS_CODE_CANCELED ? [
                'category_id' => $order->cancellation_reason_category_id,
                'category_name' => $order->cancellationReasonCategory?->name,
                'allows_free_text' => $order->cancellationReasonCategory?->allows_free_text,
                'reason_text' => $order->cancellation_reason_text,
            ] : null,
            'seller_subtotal_amount' => $items->sum('line_total'),
            'seller_subtotal_amount_label' => $this->moneyLabel($items->sum('line_total')),
            'subtotal_amount' => $order->subtotal_amount,
            'subtotal_amount_label' => $this->moneyLabel((int) $order->subtotal_amount),
            'delivery_fee' => $order->delivery_fee,
            'delivery_fee_label' => $this->moneyLabel((int) $order->delivery_fee),
            'discount_amount' => $order->discount_amount,
            'discount_amount_label' => $this->moneyLabel((int) $order->discount_amount),
            'total_amount' => $order->total_amount,
            'total_amount_label' => $this->moneyLabel((int) $order->total_amount),
            'total_items' => $items->sum('quantity'),
            'items' => $items->map(fn (TransactionItem $item): array => [
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
            'delivery_method' => $order->delivery_method,
            'pickup_time_option' => $order->pickup_time_option,
            'pickup_scheduled_at' => $order->pickup_scheduled_at,
            'payment_method' => $order->payment_method,
            'payment_method_option_name' => $order->payment_method_option_name,
            'transaction_at' => $order->transaction_at?->toIso8601String(),
            'transaction_at_label' => $order->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
            'status_timelines' => $order->statusHistories->map(fn ($history): array => [
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
        ];
    }

    private function sellerItems(Transaction $order, string $sellerId)
    {
        return $order->items
            ->filter(fn (TransactionItem $item): bool => $item->tenant?->owner_user_id === $sellerId)
            ->values();
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Diterima Toko',
            Transaction::STATUS_PROCESSING => 'Sedang Diproses',
            Transaction::STATUS_ON_THE_WAY => 'Dalam Perjalanan',
            Transaction::STATUS_READY_FOR_PICKUP => 'Siap Diambil',
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
