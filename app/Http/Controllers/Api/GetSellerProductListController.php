<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSellerProductListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('tenant')
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'tenant_name' => $product->tenant?->name,
                'name' => $product->name,
                'category' => $product->category,
                'image_url' => $product->image_url,
                'price' => $product->price,
                'original_price' => $product->original_price,
                'weight_label' => $product->weight_label,
                'description' => $product->description,
                'delivery_estimate' => $product->delivery_estimate,
            ])
            ->values();

        return response()->json([
            'message' => 'Daftar produk seller berhasil diambil.',
            'data' => $products,
        ]);
    }
}
