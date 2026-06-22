<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\SellerOrderResponseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GetSellerOrderListController extends Controller
{
    public function __construct(private readonly SellerOrderResponseMapper $responseMapper) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_code' => ['nullable', 'string', Rule::in(Transaction::statusCodes())],
        ]);

        $sellerId = $request->user()->id;
        $status = Transaction::statusFromCode($validated['status_code'] ?? null);

        $orders = Transaction::query()
            ->with(['items.tenant', 'user'])
            ->whereHas('items.tenant', fn ($query) => $query->where('owner_user_id', $sellerId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar order seller berhasil diambil.',
            'data' => $orders->getCollection()
                ->map(fn (Transaction $order): array => $this->mapOrder($order, $sellerId))
                ->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
            'links' => [
                'first' => $orders->url(1),
                'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(),
                'next' => $orders->nextPageUrl(),
            ],
        ]);
    }

    private function mapOrder(Transaction $order, string $sellerId): array
    {
        $items = $this->sellerItems($order, $sellerId);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            ...$this->responseMapper->sharedFields($order),
            'store_name' => $items->first()?->tenant?->name,
            'total_items' => $items->sum('quantity'),
            'seller_subtotal_amount' => $items->sum('line_total'),
            'seller_subtotal_amount_label' => $this->moneyLabel($items->sum('line_total')),
            'total_amount' => $order->total_amount,
            'total_amount_label' => $this->moneyLabel((int) $order->total_amount),
            'status' => $order->status,
            'status_code' => $order->statusCode(),
            'transaction_at' => $order->transaction_at?->toIso8601String(),
            'transaction_at_label' => $order->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
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
        ];
    }

    private function sellerItems(Transaction $order, string $sellerId)
    {
        return $order->items
            ->filter(fn (TransactionItem $item): bool => $item->tenant?->owner_user_id === $sellerId)
            ->values();
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
