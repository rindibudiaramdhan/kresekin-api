<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetServiceFeeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $serviceFee = max(0, (int) config('api.service_fee'));

        return response()->json([
            'message' => 'Biaya layanan berhasil diambil.',
            'data' => [
                'service_fee' => $serviceFee,
                'service_fee_label' => $this->moneyLabel($serviceFee),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
