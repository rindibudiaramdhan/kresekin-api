<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertCartItemRequest;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;

class AddCartItemController extends Controller
{
    public function __invoke(UpsertCartItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cartItem = CartItem::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);

        $cartItem->quantity = ($cartItem->exists ? $cartItem->quantity : 0) + $validated['quantity'];
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
