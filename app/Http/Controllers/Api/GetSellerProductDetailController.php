<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetSellerProductDetailController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $product = Product::query()
            ->with('tenant')
            ->where('id', $id)
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Produk tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Detail produk seller berhasil diambil.',
            'data' => $this->mapProduct($product),
        ]);
    }

    private function mapProduct(Product $product): array
    {
        return [
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
        ];
    }
}
