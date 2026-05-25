<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AddCartItemController extends Controller
{
    public function __invoke(UpsertCartItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $product = Product::query()->find($validated['product_id']);

        if (! $product?->is_active) {
            return response()->json([
                'message' => 'Produk tidak tersedia.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem = CartItem::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);

        $quantity = ($cartItem->exists ? $cartItem->quantity : 0) + $validated['quantity'];

        if (! $product->hasEnoughStock($quantity)) {
            return response()->json([
                'message' => 'Stok produk tidak mencukupi.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->quantity = $quantity;
        $cartItem->save();

        return response()->json([
            'message' => 'Barang berhasil ditambahkan ke keranjang.',
            'data' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
            ],
        ], 201);
    }
}
