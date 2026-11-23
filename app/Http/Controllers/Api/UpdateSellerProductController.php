<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Support\ProductImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UpdateSellerProductController extends Controller
{
    public function __invoke(UpdateSellerProductRequest $request, string $id): JsonResponse
    {
        $imageStorage = new ProductImageStorage;
        $uploadedImagePath = null;
        $previousImagePath = null;

        try {
            $product = DB::transaction(function () use ($request, $id, $imageStorage, &$uploadedImagePath, &$previousImagePath): ?Product {
                $product = Product::query()
                    ->where('id', $id)
                    ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    return null;
                }

                $validated = $request->safe()->except('image');
                $this->syncPrimaryCategory($product, $validated['category']);

                $productUnit = ProductUnit::query()->findOrFail($validated['product_unit_id']);
                $validated['unit'] = $productUnit->name;

                if ($request->hasFile('image')) {
                    $uploadedImagePath = $imageStorage->store($request->file('image'));
                    $validated['image_path'] = $uploadedImagePath;
                    $validated['image_url'] = $imageStorage->url($uploadedImagePath);
                } elseif ($request->filled('image_path')) {
                    $validated['image_url'] = $imageStorage->url($validated['image_path']);
                } elseif ($request->has('image_url')) {
                    $validated['image_path'] = null;
                }

                if (isset($validated['image_path']) || $request->has('image_url')) {
                    $previousImagePath = $product->image_path;
                }

                if ($request->has('is_active')) {
                    $validated['is_active'] = $request->boolean('is_active');
                } else {
                    unset($validated['is_active']);
                }

                $product->update($validated);

                return $product;
            });
        } catch (Throwable $exception) {
            $imageStorage->delete($uploadedImagePath);

            throw $exception;
        }

        if (! $product) {
            return response()->json([
                'message' => 'Produk tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($previousImagePath !== null && $previousImagePath !== $product->image_path) {
            $imageStorage->delete($previousImagePath);
        }

        $product->load(['tenant', 'productUnit']);

        return response()->json([
            'message' => 'Produk seller berhasil diperbarui.',
            'data' => $this->mapProduct($product),
        ]);
    }

    private function syncPrimaryCategory(Product $product, string $categoryName): void
    {
        $categoryIds = ProductCategory::query()
            ->where('name', $categoryName)
            ->limit(2)
            ->pluck('id');

        if ($categoryIds->count() !== 1) {
            throw ValidationException::withMessages([
                'category' => 'Nama kategori harus cocok dengan tepat satu kategori di master.',
            ]);
        }

        $categories = $product->productCategories();

        if ($product->category !== $categoryName) {
            $previousCategoryIds = $categories
                ->where('product_categories.name', $product->category)
                ->limit(2)
                ->pluck('product_categories.id');

            if ($previousCategoryIds->count() > 1) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori utama sebelumnya tidak dapat ditentukan dari nama kategori.',
                ]);
            }

            $categories->detach($previousCategoryIds->all());
        }

        $product->productCategories()->syncWithoutDetaching($categoryIds->all());
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
