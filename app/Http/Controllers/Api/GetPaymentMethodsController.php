<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PaymentMethodCatalog;
use Illuminate\Http\JsonResponse;

class GetPaymentMethodsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar metode pembayaran berhasil diambil.',
            'data' => collect(PaymentMethodCatalog::all())
                ->values()
                ->map(fn (array $method): array => [
                    'id' => $method['id'],
                    'code' => $method['code'],
                    'name' => $method['name'],
                    'icon_key' => $method['icon_key'],
                    'requires_option' => $method['requires_option'],
                    'options' => collect($method['options'])->map(fn (array $option): array => [
                        'id' => $option['id'],
                        'code' => $option['code'],
                        'name' => $option['name'],
                        'icon_key' => $option['icon_key'],
                    ])->values(),
                ])->values(),
        ]);
    }
}
