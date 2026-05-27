<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerProductStatusRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerProductStatusController extends Controller
{
    public function __invoke(UpdateSellerProductStatusRequest $request, string $id): JsonResponse
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

        $product->forceFill([
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return response()->json([
            'message' => 'Status produk seller berhasil diperbarui.',
            'data' => [
                'id' => $product->id,
                'is_active' => $product->is_active,
                'status_label' => $product->is_active ? 'Aktif' : 'Nonaktif',
            ],
        ]);
    }
}
