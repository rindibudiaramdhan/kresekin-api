<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerProductRequest;
use App\Models\Product;
use App\Support\ProductImageStorage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerProductController extends Controller
{
    public function __invoke(UpdateSellerProductRequest $request, string $id): JsonResponse
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

        $validated = $request->safe()->except('image');
        $imageStorage = new ProductImageStorage;

        if ($request->hasFile('image')) {
            $imageStorage->delete($product->image_path);
            $validated['image_path'] = $imageStorage->store($request->file('image'));
            $validated['image_url'] = $imageStorage->url($validated['image_path']);
        } elseif ($request->filled('image_path')) {
            if ($product->image_path !== $validated['image_path']) {
                $imageStorage->delete($product->image_path);
            }

            $validated['image_url'] = $imageStorage->url($validated['image_path']);
        } elseif ($request->has('image_url')) {
            $imageStorage->delete($product->image_path);
            $validated['image_path'] = null;
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        } else {
            unset($validated['is_active']);
        }

        $product->update($validated);
        $product->load('tenant');

        return response()->json([
            'message' => 'Produk seller berhasil diperbarui.',
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
            'image_url' => $product->publicImageUrl(),
            'price' => $product->price,
            'original_price' => $product->original_price,
            'stock' => $product->stock,
            'unit' => $product->unit,
            'minimum_stock' => $product->minimum_stock,
            'is_low_stock' => $product->isLowStock(),
            'is_active' => $product->is_active,
            'weight_label' => $product->weight_label,
            'description' => $product->description,
            'delivery_estimate' => $product->delivery_estimate,
        ];
    }
}
