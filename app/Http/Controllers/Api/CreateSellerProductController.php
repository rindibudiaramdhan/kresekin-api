<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSellerProductRequest;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Support\ProductImageStorage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateSellerProductController extends Controller
{
    public function __invoke(CreateSellerProductRequest $request): JsonResponse
    {
        $validated = $request->safe()->except('image');
        $validated['minimum_stock'] = $validated['minimum_stock'] ?? 1;
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $productUnit = ProductUnit::query()->findOrFail($validated['product_unit_id']);
        $validated['unit'] = $productUnit->name;

        if ($request->hasFile('image')) {
            $imageStorage = new ProductImageStorage;
            $validated['image_path'] = $imageStorage->store($request->file('image'));
            $validated['image_url'] = $imageStorage->url($validated['image_path']);
        } elseif (! empty($validated['image_path'])) {
            $validated['image_url'] = (new ProductImageStorage)->url($validated['image_path']);
        }

        $product = Product::query()->create($validated);
        $product->load(['tenant', 'productUnit']);

        return response()->json([
            'message' => 'Produk seller berhasil dibuat.',
            'data' => $this->mapProduct($product),
        ], Response::HTTP_CREATED);
    }

    private function mapProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'tenant_name' => $product->tenant?->name,
            'name' => $product->name,
            'category' => $product->category,
            'image_url' => $product->publicImageUrl(),
            'price' => $product->price,
            'original_price' => $product->original_price,
            'stock' => $product->stock,
            'unit' => $product->unit,
            'product_unit_id' => $product->product_unit_id,
            'product_unit' => $product->productUnit ? [
                'id' => $product->productUnit->id,
                'name' => $product->productUnit->name,
                'slug' => $product->productUnit->slug,
            ] : null,
            'minimum_stock' => $product->minimum_stock,
            'is_low_stock' => $product->isLowStock(),
            'is_active' => $product->is_active,
            'weight_label' => $product->weight_label,
            'description' => $product->description,
            'delivery_estimate' => $product->delivery_estimate,
        ];
    }
}
