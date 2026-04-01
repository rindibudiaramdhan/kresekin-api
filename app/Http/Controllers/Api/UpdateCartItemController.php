<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetCartItemQuantityRequest;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateCartItemController extends Controller
{
    public function __invoke(SetCartItemQuantityRequest $request, int $id): JsonResponse
    {
        $cartItem = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $cartItem) {
            return response()->json([
                'message' => 'Item keranjang tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
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
