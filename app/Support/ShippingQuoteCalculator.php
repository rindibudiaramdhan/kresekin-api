<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantShippingSetting;
use Illuminate\Validation\ValidationException;

class ShippingQuoteCalculator
{
    public function calculate(Tenant $tenant, TenantShippingSetting $settings, float $latitude, float $longitude): array
    {
        $distance = null;
        if ($settings->mode === 'distance' || $settings->max_distance_km !== null) {
            if ($tenant->latitude === null || $tenant->longitude === null) {
                throw ValidationException::withMessages(['tenant' => ['Koordinat toko belum tersedia.']]);
            }

            $lat1 = deg2rad($tenant->latitude);
            $lat2 = deg2rad($latitude);
            $a = sin(($lat2 - $lat1) / 2) ** 2
                + cos($lat1) * cos($lat2) * sin(deg2rad($longitude - $tenant->longitude) / 2) ** 2;
            $distance = 6371 * 2 * asin(sqrt(max(0, min(1, $a))));
        }

        $deliverable = $settings->max_distance_km === null || $distance <= $settings->max_distance_km;
        $fee = match ($settings->mode) {
            'free' => 0,
            'flat' => $settings->flat_fee,
            'distance' => (int) ceil($distance * $settings->fee_per_km),
        };

        return [
            'tenant_id' => $tenant->id,
            'mode' => $settings->mode,
            'distance_km' => $distance === null ? null : round($distance, 3),
            'distance_method' => $distance === null ? null : 'straight_line',
            'delivery_fee' => $deliverable ? $fee : null,
            'is_deliverable' => $deliverable,
        ];
    }
}
