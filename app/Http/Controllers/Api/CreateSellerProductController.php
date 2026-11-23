<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSellerProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Support\ProductImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CreateSellerProductController extends Controller
{
    public function __invoke(CreateSellerProductRequest $request): JsonResponse
    {
        $validated = $request->safe()->except('image');
        $validated['minimum_stock'] = $validated['minimum_stock'] ?? 1;
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $productCategory = ProductCategory::query()->findOrFail($validated['product_category_id']);
        $validated['category'] = $productCategory->name;
        unset($validated['product_category_id']);
        $productUnit = ProductUnit::query()->findOrFail($validated['product_unit_id']);
        $validated['unit'] = $productUnit->name;

        $imageStorage = new ProductImageStorage;
        $uploadedImagePath = null;

        try {
            if ($request->hasFile('image')) {
                $uploadedImagePath = $imageStorage->store($request->file('image'));
                $validated['image_path'] = $uploadedImagePath;
            }

            if (! empty($validated['image_path'])) {
                $validated['image_url'] = $imageStorage->url($validated['image_path']);
            }

            $product = DB::transaction(function () use ($validated, $productCategory): Product {
                $product = Product::query()->create($validated);
                $product->productCategories()->attach($productCategory->id);

                return $product;
            });
        } catch (Throwable $exception) {
            $imageStorage->delete($uploadedImagePath);

            throw $exception;
        }

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
