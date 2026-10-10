<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerTenantStatusRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerTenantStatusController extends Controller
{
    public function __invoke(UpdateSellerTenantStatusRequest $request, string $id): JsonResponse
    {
        $tenant = $request->user()->ownedTenants()->whereKey($id)->first();

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $tenant->forceFill(['is_open' => $request->boolean('is_open')])->save();

        return response()->json([
            'message' => 'Status toko seller berhasil diperbarui.',
            'data' => [
                'id' => $tenant->id,
                'is_open' => $tenant->is_open,
                'store_status' => $tenant->is_open ? 'Buka' : 'Tutup',
            ],
        ]);
    }
}
