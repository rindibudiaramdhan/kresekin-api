<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSellerProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateSellerProductController extends Controller
{
    public function __invoke(CreateSellerProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());
        $product->load('tenant');

        return response()->json([
            'message' => 'Produk seller berhasil dibuat.',
            'data' => [
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
            ],
        ], Response::HTTP_CREATED);
    }
}
