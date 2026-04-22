<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteSellerProductController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $product = Product::query()
            ->where('id', $id)
            ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Produk tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produk seller berhasil dihapus.',
        ]);
    }
}
