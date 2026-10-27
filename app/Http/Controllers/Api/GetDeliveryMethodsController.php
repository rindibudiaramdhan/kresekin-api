<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Support\CartShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDeliveryMethodsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $deliveryMethods = DeliveryMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $items = CartItem::query()->with('product.tenant')->where('user_id', $request->user()->id)->get();
        $calculator = app(CartShippingCalculator::class);

        return response()->json([
            'message' => 'Daftar metode pengiriman berhasil diambil.',
            'data' => $deliveryMethods->map(function (DeliveryMethod $method) use ($calculator, $items, $request): array {
                $quote = $calculator->calculate($items, $request->user(), $method);

                return [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'description' => $method->description,
                    'fee' => $quote['delivery_fee'],
                    'fee_label' => $quote['delivery_fee'] === null ? null : $this->moneyLabel($quote['delivery_fee']),
                    'shipping_snapshot' => $quote['shipping_snapshot'],
                    'is_deliverable' => $quote['is_deliverable'],
                    'shipping_error' => $quote['shipping_error'],
                    'requires_order_time' => $method->requires_order_time,
                ];
            })->values(),
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
