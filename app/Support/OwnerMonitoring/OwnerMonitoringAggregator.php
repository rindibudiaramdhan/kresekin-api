<?php

namespace App\Support\OwnerMonitoring;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OwnerMonitoringAggregator
{
    public function summary(OwnerMonitoringContext $context): array
    {
        $completed = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereIn('transaction_items.tenant_id', $context->storeIds)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transactions.transaction_at', [$context->start, $context->end])
            ->selectRaw('COALESCE(SUM(transaction_items.line_total), 0) as sales_amount')
            ->selectRaw('COUNT(DISTINCT transaction_items.transaction_id) as order_count')
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as item_quantity')
            ->selectRaw('COUNT(DISTINCT transaction_items.tenant_id) as active_store_count')
            ->first();

        $rawStatusCounts = Transaction::query()
            ->whereBetween('transaction_at', [$context->start, $context->end])
            ->whereHas('items', fn (Builder $query) => $query->whereIn('tenant_id', $context->storeIds))
            ->select('status')
            ->selectRaw('COUNT(DISTINCT transactions.id) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = collect(Transaction::statusMap())
            ->map(fn (string $status, string $code): array => [
                'status_code' => $code,
                'status_label' => $this->statusLabel($code),
                'count' => (int) ($rawStatusCounts[$status] ?? 0),
            ])
            ->values()
            ->all();

        $salesAmount = (int) ($completed?->sales_amount ?? 0);

        return [
            'generated_at' => now('Asia/Jakarta')->toIso8601String(),
            'refresh_after_seconds' => 10,
            'scope' => $context->scopePayload(),
            'summary' => [
                'sales_amount' => $salesAmount,
                'sales_amount_label' => $this->moneyLabel($salesAmount),
                'order_count' => (int) ($completed?->order_count ?? 0),
                'item_quantity' => (int) ($completed?->item_quantity ?? 0),
                'active_store_count' => (int) ($completed?->active_store_count ?? 0),
            ],
            'order_status_counts' => $statusCounts,
        ];
    }

    public function stores(OwnerMonitoringContext $context, array $filters): LengthAwarePaginator
    {
        $completed = Transaction::STATUS_COMPLETED;
        $sort = $filters['sort'] ?? 'sales_amount';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 25);

        $query = Tenant::query()
            ->join('users as branch_users', 'branch_users.id', '=', 'tenants.owner_user_id')
            ->leftJoin('transaction_items', 'transaction_items.tenant_id', '=', 'tenants.id')
            ->leftJoin('transactions', function ($join) use ($context): void {
                $join->on('transactions.id', '=', 'transaction_items.transaction_id')
                    ->whereBetween('transactions.transaction_at', [$context->start, $context->end]);
            })
            ->whereIn('tenants.id', $context->storeIds)
            ->groupBy('tenants.id', 'tenants.name', 'branch_users.id', 'branch_users.name')
            ->select([
                'tenants.id as store_id',
                'tenants.name as store_name',
                'branch_users.id as seller_id',
                'branch_users.name as branch_name',
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.status = ? THEN transaction_items.line_total ELSE 0 END), 0) as sales_amount', [$completed])
            ->selectRaw('COUNT(DISTINCT CASE WHEN transactions.status = ? THEN transactions.id END) as order_count', [$completed])
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.status = ? THEN transaction_items.quantity ELSE 0 END), 0) as item_quantity', [$completed])
            ->selectRaw('MAX(transactions.transaction_at) as last_order_at');

        $query->orderBy($sort, $direction)->orderBy('tenants.id');

        return $query->paginate($perPage)->through(fn ($row): array => [
            'store_id' => $row->store_id,
            'store_name' => $row->store_name,
            'seller_id' => $row->seller_id,
            'branch_name' => $row->branch_name,
            'sales_amount' => (int) $row->sales_amount,
            'sales_amount_label' => $this->moneyLabel((int) $row->sales_amount),
            'order_count' => (int) $row->order_count,
            'item_quantity' => (int) $row->item_quantity,
            'last_order_at' => $row->last_order_at,
            'last_order_at_label' => $row->last_order_at
                ? CarbonImmutable::parse($row->last_order_at)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i').' WIB'
                : null,
        ]);
    }

    public function orders(OwnerMonitoringContext $context, array $filters): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->select(['id', 'order_number', 'status', 'transaction_at'])
            ->with(['items' => fn ($query) => $query
                ->whereIn('tenant_id', $context->storeIds)
                ->with('tenant.owner')])
            ->whereBetween('transaction_at', [$context->start, $context->end])
            ->whereHas('items', fn (Builder $query) => $query->whereIn('tenant_id', $context->storeIds))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', Transaction::statusFromCode($status)))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('order_number', 'like', '%'.$search.'%'))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id');

        return $query->paginate((int) ($filters['per_page'] ?? 25))->through(function (Transaction $transaction): array {
            $items = $transaction->items;
            $amount = (int) $items->sum('line_total');
            $branches = $items->map(fn (TransactionItem $item) => $item->tenant?->owner)
                ->filter()
                ->unique('id')
                ->map(fn ($branch): array => ['id' => $branch->id, 'name' => $branch->name])
                ->values()
                ->all();
            $stores = $items->map(fn (TransactionItem $item) => $item->tenant)
                ->filter()
                ->unique('id')
                ->map(fn (Tenant $store): array => ['id' => $store->id, 'name' => $store->name])
                ->values()
                ->all();

            return [
                'id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'branches' => $branches,
                'stores' => $stores,
                'amount' => $amount,
                'amount_label' => $this->moneyLabel($amount),
                'status_code' => $transaction->statusCode(),
                'status_label' => $this->statusLabel($transaction->statusCode()),
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i').' WIB',
            ];
        });
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function statusLabel(?string $statusCode): string
    {
        return match ($statusCode) {
            Transaction::STATUS_CODE_PENDING_PAYMENT => 'Menunggu Pembayaran',
            Transaction::STATUS_CODE_ACCEPTED_BY_STORE => 'Diterima Toko',
            Transaction::STATUS_CODE_PROCESSING => 'Sedang Diproses',
            Transaction::STATUS_CODE_ON_THE_WAY => 'Dalam Perjalanan',
            Transaction::STATUS_CODE_READY_FOR_PICKUP => 'Siap Diambil',
            Transaction::STATUS_CODE_COMPLETED => 'Pesanan Selesai',
            Transaction::STATUS_CODE_CANCELED => 'Pesanan Dibatalkan',
            default => 'Tidak Diketahui',
        };
    }
}
