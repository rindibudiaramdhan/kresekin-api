<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCartController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cartItems = CartItem::query()
            ->with(['product.tenant'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $subtotal = $cartItems->sum(fn (CartItem $item): int => $item->quantity * $item->product->price);

        return response()->json([
            'message' => 'Keranjang berhasil diambil.',
            'data' => [
                'items' => $cartItems->map(fn (CartItem $item): array => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image_url' => $item->product->image_url,
                        'price' => $item->product->price,
                        'price_label' => $this->moneyLabel($item->product->price),
                        'weight_label' => $item->product->weight_label,
                        'tenant_id' => $item->product->tenant_id,
                        'tenant_name' => $item->product->tenant?->name,
                    ],
                    'line_total' => $item->quantity * $item->product->price,
                    'line_total_label' => $this->moneyLabel($item->quantity * $item->product->price),
                ])->values(),
                'subtotal' => $subtotal,
                'subtotal_label' => $this->moneyLabel($subtotal),
                'total_items' => $cartItems->sum('quantity'),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
