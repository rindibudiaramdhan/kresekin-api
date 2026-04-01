<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\DeliveryMethodCatalog;
use Illuminate\Http\JsonResponse;

class GetDeliveryMethodsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $deliveryMethods = DeliveryMethodCatalog::all();

        return response()->json([
            'message' => 'Daftar metode pengiriman berhasil diambil.',
            'data' => collect($deliveryMethods)->values()->map(fn (array $method): array => [
                'id' => $method['id'],
                'code' => $method['code'],
                'name' => $method['name'],
                'description' => $method['description'],
                'fee' => $method['fee'],
                'fee_label' => $this->moneyLabel($method['fee']),
            ])->values(),
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
