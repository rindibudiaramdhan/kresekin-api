<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetCartItemQuantityRequest;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateCartItemController extends Controller
{
    public function __invoke(SetCartItemQuantityRequest $request, string $id): JsonResponse
    {
        $cartItem = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $cartItem) {
            return response()->json([
                'message' => 'Item keranjang tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $cartItem->load('product');

        if (! $cartItem->product?->is_active) {
            return response()->json([
                'message' => 'Produk tidak tersedia.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $cartItem->product->hasEnoughStock($request->validated()['quantity'])) {
            return response()->json([
                'message' => 'Stok produk tidak mencukupi.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->forceFill([
            'quantity' => $request->validated()['quantity'],
        ])->save();

        return response()->json([
            'message' => 'Jumlah barang di keranjang berhasil diperbarui.',
            'data' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
            ],
        ]);
    }
}
