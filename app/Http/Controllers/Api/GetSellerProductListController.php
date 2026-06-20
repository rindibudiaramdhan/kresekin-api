<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GetSellerProductListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'name' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $search = $validated['name'] ?? $validated['search'] ?? null;

        $products = Product::query()
            ->with(['tenant', 'productUnit'])
            ->withSum([
                'transactionItems as sold' => fn ($query) => $query
                    ->whereHas('transaction', fn ($transactionQuery) => $transactionQuery
                        ->where('status', Transaction::STATUS_COMPLETED)),
            ], 'quantity')
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->when(
                $search,
                fn ($query, string $search) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.str($search)->lower()->toString().'%'])
            )
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'tenant_name' => $product->tenant?->name,
                'created_at' => $product->created_at?->toIso8601String(),
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
                'sold' => (int) ($product->sold ?? 0),
            ])
            ->values();

        return response()->json([
            'message' => 'Daftar produk seller berhasil diambil.',
            'data' => $products,
        ]);
    }
}
