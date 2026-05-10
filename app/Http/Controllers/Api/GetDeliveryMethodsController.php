<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use Illuminate\Http\JsonResponse;

class GetDeliveryMethodsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $deliveryMethods = DeliveryMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Daftar metode pengiriman berhasil diambil.',
            'data' => $deliveryMethods->map(fn (DeliveryMethod $method): array => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'description' => $method->description,
                'fee' => $method->fee,
                'fee_label' => $this->moneyLabel($method->fee),
                'requires_order_time' => $method->requires_order_time,
            ])->values(),
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
