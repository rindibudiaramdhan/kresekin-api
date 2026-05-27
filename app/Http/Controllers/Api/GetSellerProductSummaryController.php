<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSellerProductSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $baseQuery = Product::query()
            ->whereHas('tenant', fn (Builder $query) => $query->where('owner_user_id', $sellerId));

        return response()->json([
            'message' => 'Ringkasan produk seller berhasil diambil.',
            'data' => [
                'total_products' => (clone $baseQuery)->count(),
                'active_products' => (clone $baseQuery)->where('is_active', true)->count(),
                'inactive_products' => (clone $baseQuery)->where('is_active', false)->count(),
                'low_stock_products' => (clone $baseQuery)
                    ->whereNotNull('stock')
                    ->whereColumn('stock', '<=', 'minimum_stock')
                    ->count(),
            ],
        ]);
    }
}
