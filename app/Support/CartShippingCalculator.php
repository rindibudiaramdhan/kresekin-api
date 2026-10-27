<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Models\Tenant;
use App\Models\TenantShippingSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartShippingCalculator
{
    public function calculate(Collection $items, User $buyer, ?DeliveryMethod $method, bool $lock = false): array
    {
        $tenants = $items->map(fn (CartItem $item) => $item->product?->tenant)->filter()->unique('id')->values();
        if ($tenants->count() > 1) {
            return $this->unavailable('Checkout hanya dapat berisi produk dari satu toko.');
        }

        if (! $method || $items->isEmpty()) {
            return $this->available((int) ($method?->fee ?? 0));
        }

        if ($tenants->isEmpty()) {
            return $this->unavailable('Toko tidak ditemukan.');
        }

        $tenant = $tenants->first();
        if ($lock) {
            $tenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (! $tenant) {
                return $this->unavailable('Toko tidak ditemukan.');
            }
        }
        if ($method->code !== 'store_courier') {
            return $this->available((int) $method->fee);
        }

        $settings = TenantShippingSetting::query()->where('tenant_id', $tenant->id)->when($lock, fn ($query) => $query->lockForUpdate())->first();
        if (! $settings) {
            return $this->available((int) $method->fee, [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'mode' => 'legacy',
                'flat_fee' => (int) $method->fee,
                'fee_per_km' => null,
                'max_distance_km' => null,
                'distance_km' => null,
                'distance_method' => null,
                'delivery_fee' => (int) $method->fee,
            ]);
        }

        $needsCoordinates = $settings->mode === 'distance' || $settings->max_distance_km !== null;
        if ($needsCoordinates && ($buyer->latitude === null || $buyer->longitude === null)) {
            return $this->unavailable('Koordinat alamat pembeli belum tersedia.');
        }

        try {
            $quote = app(ShippingQuoteCalculator::class)->calculate(
                $tenant, $settings, (float) $buyer->latitude, (float) $buyer->longitude,
            );
        } catch (ValidationException $exception) {
            return $this->unavailable(collect($exception->errors())->flatten()->first());
        }

        if (! $quote['is_deliverable']) {
            return $this->unavailable('Alamat pembeli berada di luar jangkauan pengantaran toko.');
        }

        return $this->available($quote['delivery_fee'], [
            ...$quote,
            'tenant_name' => $tenant->name,
            'flat_fee' => $settings->flat_fee,
            'fee_per_km' => $settings->fee_per_km,
            'max_distance_km' => $settings->max_distance_km,
        ]);
    }

    public function requireDeliverable(array $quote): void
    {
        if (! $quote['is_deliverable']) {
            throw ValidationException::withMessages(['delivery_method_code' => [$quote['shipping_error']]]);
        }
    }

    private function available(int $fee, ?array $snapshot = null): array
    {
        return ['delivery_fee' => $fee, 'shipping_snapshot' => $snapshot, 'is_deliverable' => true, 'shipping_error' => null];
    }

    private function unavailable(string $message): array
    {
        return ['delivery_fee' => null, 'shipping_snapshot' => null, 'is_deliverable' => false, 'shipping_error' => $message];
    }
}
