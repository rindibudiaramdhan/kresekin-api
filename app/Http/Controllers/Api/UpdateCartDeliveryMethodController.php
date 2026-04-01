<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCartDeliveryMethodRequest;
use App\Models\Cart;
use App\Support\DeliveryMethodCatalog;
use Illuminate\Http\JsonResponse;

class UpdateCartDeliveryMethodController extends Controller
{
    public function __invoke(UpdateCartDeliveryMethodRequest $request): JsonResponse
    {
        $deliveryMethodCode = $request->validated()['delivery_method_code'];

        $cart = Cart::query()->firstOrNew([
            'user_id' => $request->user()->id,
        ]);

        $cart->delivery_method_code = $deliveryMethodCode;
        $cart->save();

        $deliveryMethod = DeliveryMethodCatalog::find($deliveryMethodCode);

        return response()->json([
            'message' => 'Metode pengiriman keranjang berhasil diperbarui.',
            'data' => [
                'delivery_method' => [
                    'id' => $deliveryMethod['id'],
                    'code' => $deliveryMethod['code'],
                    'name' => $deliveryMethod['name'],
                    'description' => $deliveryMethod['description'],
                    'fee' => $deliveryMethod['fee'],
                    'fee_label' => $this->moneyLabel($deliveryMethod['fee']),
                ],
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
