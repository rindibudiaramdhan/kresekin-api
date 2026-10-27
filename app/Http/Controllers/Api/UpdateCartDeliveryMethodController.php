<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCartDeliveryMethodRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Support\CartShippingCalculator;
use Illuminate\Http\JsonResponse;

class UpdateCartDeliveryMethodController extends Controller
{
    public function __invoke(UpdateCartDeliveryMethodRequest $request): JsonResponse
    {
        $deliveryMethodCode = $request->validated()['delivery_method_code'];

        $cart = Cart::query()->firstOrNew([
            'user_id' => $request->user()->id,
        ]);

        $deliveryMethod = DeliveryMethod::query()
            ->active()
            ->where('code', strtolower(trim($deliveryMethodCode)))
            ->firstOrFail();

        $items = CartItem::query()->with('product.tenant')->where('user_id', $request->user()->id)->get();
        $calculator = app(CartShippingCalculator::class);
        $quote = $calculator->calculate($items, $request->user(), $deliveryMethod);
        $calculator->requireDeliverable($quote);
        $cart->delivery_method_code = $deliveryMethodCode;
        $cart->save();

        return response()->json([
            'message' => 'Metode pengiriman keranjang berhasil diperbarui.',
            'data' => [
                'delivery_method' => [
                    'id' => $deliveryMethod->id,
                    'code' => $deliveryMethod->code,
                    'name' => $deliveryMethod->name,
                    'description' => $deliveryMethod->description,
                    'fee' => $quote['delivery_fee'],
                    'fee_label' => $this->moneyLabel($quote['delivery_fee']),
                    'shipping_snapshot' => $quote['shipping_snapshot'],
                    'is_deliverable' => true,
                    'requires_order_time' => $deliveryMethod->requires_order_time,
                ],
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
