<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingQuoteRequest;
use App\Models\Tenant;
use App\Models\TenantShippingSetting;
use App\Support\ShippingQuoteCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShippingQuoteController extends Controller
{
    public function __invoke(ShippingQuoteRequest $request, string $tenantId, ShippingQuoteCalculator $calculator): JsonResponse
    {
        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Tenant tidak ditemukan.'], 404);
        }

        $settings = TenantShippingSetting::query()->where('tenant_id', $tenantId)->first();
        if (! $settings) {
            throw ValidationException::withMessages(['shipping_settings' => ['Pengaturan ongkir toko belum tersedia.']]);
        }

        $data = $request->validated();

        return response()->json([
            'message' => 'Estimasi ongkir berhasil dihitung.',
            'data' => $calculator->calculate($tenant, $settings, (float) $data['latitude'], (float) $data['longitude']),
        ]);
    }
}
