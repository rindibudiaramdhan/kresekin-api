<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteCartItemController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $cartItem = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $cartItem) {
            return response()->json([
                'message' => 'Item keranjang tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Barang berhasil dihapus dari keranjang.',
        ]);
    }
}
