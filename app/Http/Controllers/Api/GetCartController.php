<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Support\DeliveryMethodCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCartController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cart = Cart::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $cartItems = CartItem::query()
            ->with(['product.tenant'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $subtotal = $cartItems->sum(fn (CartItem $item): int => $item->quantity * $item->product->price);
        $deliveryMethod = DeliveryMethodCatalog::find($cart->delivery_method_code);
        $deliveryFee = $deliveryMethod['fee'] ?? 0;
        $grandTotal = $subtotal + $deliveryFee;

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
                'delivery_method' => $deliveryMethod ? [
                    'id' => $deliveryMethod['id'],
                    'code' => $deliveryMethod['code'],
                    'name' => $deliveryMethod['name'],
                    'description' => $deliveryMethod['description'],
                    'fee' => $deliveryMethod['fee'],
                    'fee_label' => $this->moneyLabel($deliveryMethod['fee']),
                ] : null,
                'subtotal' => $subtotal,
                'subtotal_label' => $this->moneyLabel($subtotal),
                'delivery_fee' => $deliveryFee,
                'delivery_fee_label' => $this->moneyLabel($deliveryFee),
                'grand_total' => $grandTotal,
                'grand_total_label' => $this->moneyLabel($grandTotal),
                'total_items' => $cartItems->sum('quantity'),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
