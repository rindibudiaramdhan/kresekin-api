<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodOption;
use Illuminate\Http\JsonResponse;

class GetPaymentMethodsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $paymentMethods = PaymentMethod::query()
            ->active()
            ->with('activeOptions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Daftar metode pembayaran berhasil diambil.',
            'data' => $paymentMethods
                ->map(fn (PaymentMethod $method): array => [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'icon_key' => $method->icon_key,
                    'requires_option' => $method->requires_option,
                    'options' => $method->activeOptions->map(fn (PaymentMethodOption $option): array => [
                        'id' => $option->id,
                        'code' => $option->code,
                        'name' => $option->name,
                        'icon_key' => $option->icon_key,
                    ])->values(),
                ])->values(),
        ]);
    }
}
