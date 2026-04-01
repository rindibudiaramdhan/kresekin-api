<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetDeliveryMethodsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $deliveryMethods = [
            [
                'id' => 1,
                'code' => 'store_courier',
                'name' => 'Antar Kurir Toko',
                'description' => 'Diantar hari ini',
                'fee' => 2500,
            ],
            [
                'id' => 2,
                'code' => 'pickup',
                'name' => 'Ambil ke Toko',
                'description' => null,
                'fee' => 0,
            ],
        ];

        return response()->json([
            'message' => 'Daftar metode pengiriman berhasil diambil.',
            'data' => collect($deliveryMethods)->map(fn (array $method): array => [
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
