<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OrderTimeOptionCatalog;
use Illuminate\Http\JsonResponse;

class GetOrderTimeOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $orderTimeOptions = OrderTimeOptionCatalog::all();

        return response()->json([
            'message' => 'Daftar pilihan waktu pesanan berhasil diambil.',
            'data' => collect($orderTimeOptions)->values()->map(fn (array $option): array => [
                'id' => $option['id'],
                'code' => $option['code'],
                'name' => $option['name'],
                'description' => $option['description'],
            ])->values(),
        ]);
    }
}
