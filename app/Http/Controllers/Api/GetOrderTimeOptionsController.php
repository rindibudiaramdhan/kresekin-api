<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderTimeOption;
use Illuminate\Http\JsonResponse;

class GetOrderTimeOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $orderTimeOptions = OrderTimeOption::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Daftar pilihan waktu pesanan berhasil diambil.',
            'data' => $orderTimeOptions->map(fn (OrderTimeOption $option): array => [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->name,
                'description' => $option->description,
                'requires_schedule' => $option->requires_schedule,
            ])->values(),
        ]);
    }
}
