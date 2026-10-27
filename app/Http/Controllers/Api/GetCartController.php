<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Support\CartShippingCalculator;
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
        $deliveryMethod = $cart->delivery_method_code
            ? DeliveryMethod::query()
                ->active()
                ->where('code', $cart->delivery_method_code)
                ->first()
            : null;
        $shippingQuote = app(CartShippingCalculator::class)->calculate($cartItems, $request->user(), $deliveryMethod);
        $deliveryFee = $shippingQuote['delivery_fee'];
        $grandTotal = $deliveryFee === null ? null : $subtotal + $deliveryFee;

        return response()->json([
            'message' => 'Keranjang berhasil diambil.',
            'data' => [
                'items' => $cartItems->map(fn (CartItem $item): array => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image_url' => $item->product->publicImageUrl(),
                        'price' => $item->product->price,
                        'price_label' => $this->moneyLabel($item->product->price),
                        'stock' => $item->product->stock,
                        'unit' => $item->product->unit,
                        'minimum_stock' => $item->product->minimum_stock,
                        'is_low_stock' => $item->product->isLowStock(),
                        'is_active' => $item->product->is_active,
                        'weight_label' => $item->product->weight_label,
                        'tenant_id' => $item->product->tenant_id,
                        'tenant_name' => $item->product->tenant?->name,
                    ],
                    'line_total' => $item->quantity * $item->product->price,
                    'line_total_label' => $this->moneyLabel($item->quantity * $item->product->price),
                ])->values(),
                'delivery_method' => $deliveryMethod ? [
                    'id' => $deliveryMethod->id,
                    'code' => $deliveryMethod->code,
                    'name' => $deliveryMethod->name,
                    'description' => $deliveryMethod->description,
                    'fee' => $deliveryFee,
                    'fee_label' => $deliveryFee === null ? null : $this->moneyLabel($deliveryFee),
                    'requires_order_time' => $deliveryMethod->requires_order_time,
                ] : null,
                'subtotal' => $subtotal,
                'subtotal_label' => $this->moneyLabel($subtotal),
                'delivery_fee' => $deliveryFee,
                'delivery_fee_label' => $deliveryFee === null ? null : $this->moneyLabel($deliveryFee),
                'shipping_snapshot' => $shippingQuote['shipping_snapshot'],
                'is_deliverable' => $shippingQuote['is_deliverable'],
                'shipping_error' => $shippingQuote['shipping_error'],
                'grand_total' => $grandTotal,
                'grand_total_label' => $grandTotal === null ? null : $this->moneyLabel($grandTotal),
                'total_items' => $cartItems->sum('quantity'),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
