<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use Illuminate\Http\JsonResponse;

class GetProductUnitsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar satuan produk berhasil diambil.',
            'data' => ProductUnit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ProductUnit $unit): array => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'slug' => $unit->slug,
                ])
                ->values(),
        ]);
    }
}
