<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTenantShippingSettingsRequest;
use App\Models\TenantShippingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerShippingSettingsController extends Controller
{
    public function show(Request $request, string $tenantId): JsonResponse
    {
        if (! $request->user()->ownedTenants()->whereKey($tenantId)->exists()) {
            return response()->json(['message' => 'Tenant tidak ditemukan.'], 404);
        }

        $settings = TenantShippingSetting::query()->where('tenant_id', $tenantId)->first();

        return response()->json([
            'message' => 'Pengaturan ongkir berhasil diambil.',
            'data' => $settings?->settingsData(),
        ]);
    }

    public function update(UpdateTenantShippingSettingsRequest $request, string $tenantId): JsonResponse
    {
        if (! $request->user()->ownedTenants()->whereKey($tenantId)->exists()) {
            return response()->json(['message' => 'Tenant tidak ditemukan.'], 404);
        }

        $data = $request->validated();
        $settings = TenantShippingSetting::query()->updateOrCreate(['tenant_id' => $tenantId], [
            'mode' => $data['mode'],
            'flat_fee' => $data['mode'] === 'flat' ? $data['flat_fee'] : null,
            'fee_per_km' => $data['mode'] === 'distance' ? $data['fee_per_km'] : null,
            'max_distance_km' => $data['max_distance_km'] ?? null,
        ]);

        return response()->json([
            'message' => 'Pengaturan ongkir berhasil disimpan.',
            'data' => $settings->settingsData(),
        ]);
    }
}
