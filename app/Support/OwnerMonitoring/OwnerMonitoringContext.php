<?php

namespace App\Support\OwnerMonitoring;

use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OwnerMonitoringContext
{
    public function __construct(
        public readonly User $owner,
        public readonly string $date,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly Collection $branches,
        public readonly Collection $stores,
        public readonly array $sellerIds,
        public readonly array $storeIds,
        public readonly ?string $selectedSellerId,
        public readonly ?string $selectedStoreId,
    ) {}

    public static function from(User $owner, array $filters): self
    {
        $date = $filters['date'];
        $localStart = CarbonImmutable::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->startOfDay();
        $localEnd = $localStart->endOfDay();

        $allBranches = User::query()
            ->where('role', User::ROLE_SELLER)
            ->where('branch_owner_user_id', $owner->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedSellerId = $filters['seller_id'] ?? null;
        $scopedBranches = $allBranches;

        if ($selectedSellerId) {
            if (! $allBranches->contains('id', $selectedSellerId)) {
                abort(404, 'Cabang tidak ditemukan.');
            }

            $scopedBranches = $allBranches->where('id', $selectedSellerId)->values();
        }

        $stores = Tenant::query()
            ->whereIn('owner_user_id', $scopedBranches->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'owner_user_id', 'name']);

        $selectedStoreId = $filters['store_id'] ?? null;
        $scopedStores = $stores;

        if ($selectedStoreId) {
            if (! $stores->contains('id', $selectedStoreId)) {
                abort(404, 'Toko tidak ditemukan.');
            }

            $scopedStores = $stores->where('id', $selectedStoreId)->values();
        }

        return new self(
            $owner,
            $date,
            $localStart->utc(),
            $localEnd->utc(),
            $allBranches,
            $stores,
            $scopedBranches->pluck('id')->all(),
            $scopedStores->pluck('id')->all(),
            $selectedSellerId,
            $selectedStoreId,
        );
    }

    public function scopePayload(): array
    {
        return [
            'seller_id' => $this->selectedSellerId,
            'store_id' => $this->selectedStoreId,
            'date' => $this->date,
            'timezone' => 'Asia/Jakarta',
            'branches' => $this->branches->map(fn (User $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
            ])->values()->all(),
            'stores' => $this->stores->map(fn (Tenant $store): array => [
                'id' => $store->id,
                'seller_id' => $store->owner_user_id,
                'name' => $store->name,
            ])->values()->all(),
        ];
    }
}
