<?php

namespace App\Support\Dashboard;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Support\AgentCommissionCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AgentDashboardAggregator
{
    public function __construct(
        private readonly AgentCommissionCalculator $calculator,
        private readonly DashboardFormatter $formatter,
    ) {}

    public function forUser(User $agent, ?string $periodValue): array
    {
        $period = DashboardPeriod::from($periodValue);
        $summary = $this->calculator->summary($agent->id);
        $tenants = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agent->id)
            ->orderBy('name')
            ->get();

        $transactionCount = $this->transactionCount($agent->id);
        $transactionTrend = $this->transactionTrend($agent->id, $period);
        $sellerCount = $tenants->pluck('owner_user_id')->filter()->unique()->count();
        $recentTransactions = Transaction::query()
            ->with(['items.tenant', 'user'])
            ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agent->id))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'meta' => $this->meta('agent', $period),
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'phone' => $agent->phone,
            ],
            'summary' => $this->summary($agent->id, $summary, $period),
            'transaction_trend' => $transactionTrend,
            'transaction_growth' => $transactionTrend,
            'umkm_spotlight' => $this->umkmSpotlight($tenants, $period),
            'top_agent_commissions' => [],
            'top_umkm_commissions' => $this->topUmkmCommissions($tenants),
            'commission_summary' => $this->commissionSummary($summary),
            'seller_count' => $sellerCount,
            'tenant_count' => $tenants->count(),
            'transaction_count' => $transactionCount,
            'stores' => $tenants->map(fn (Tenant $tenant): array => $this->mapTenant($tenant))->values(),
            'recent_transactions' => $recentTransactions
                ->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction, $agent->id))
                ->values(),
        ];
    }

    private function meta(string $role, DashboardPeriod $period): array
    {
        return [
            'role' => $role,
            'period' => $period->value,
            'generated_at' => now('Asia/Jakarta')->toIso8601String(),
            'source' => 'dashboard_aggregate_v2',
        ];
    }

    private function summary(string $agentId, array $commissionSummary, DashboardPeriod $period): array
    {
        $currentRevenue = $this->completedRevenueForAgent($agentId, $period->start, $period->end);
        $previousRevenue = $this->completedRevenueForAgent($agentId, $period->previousStart, $period->previousEnd);
        $currentOrders = $this->transactionCount($agentId, $period->start, $period->end);
        $previousOrders = $this->transactionCount($agentId, $period->previousStart, $period->previousEnd);
        $currentCommission = $this->calculator->commissionFromRevenue($currentRevenue);
        $previousCommission = $this->calculator->commissionFromRevenue($previousRevenue);
        $managedUmkm = Tenant::query()
            ->where('agent_user_id', $agentId)
            ->count();
        $managedAreas = $this->managedAreaCount($agentId);
        $activeUmkm = Tenant::query()
            ->where('agent_user_id', $agentId)
            ->whereHas('products')
            ->count();

        return [
            ...$this->withMoneyLabels($commissionSummary),
            'total_commission' => [
                'value' => (int) $commissionSummary['total_commission'],
                'formatted' => $this->formatter->money((int) $commissionSummary['total_commission']),
                'growth_percentage' => $this->formatter->growthPercentage($currentCommission, $previousCommission),
            ],
            'total_umkm_revenue' => [
                'value' => $currentRevenue,
                'formatted' => $this->formatter->money($currentRevenue),
                'growth_percentage' => $this->formatter->growthPercentage($currentRevenue, $previousRevenue),
            ],
            'total_managed_umkm_transaction_amount' => [
                'value' => $currentRevenue,
                'formatted' => $this->formatter->money($currentRevenue),
            ],
            'total_managed_umkm' => [
                'value' => $managedUmkm,
                'formatted' => $this->formatter->number($managedUmkm).' Toko',
            ],
            'total_managed_areas' => [
                'value' => $managedAreas,
                'formatted' => $this->formatter->number($managedAreas).' Area',
            ],
            'total_orders' => [
                'value' => $currentOrders,
                'formatted' => $this->formatter->number($currentOrders),
                'growth_percentage' => $this->formatter->growthPercentage($currentOrders, $previousOrders),
            ],
            'active_umkm' => [
                'value' => $activeUmkm,
                'formatted' => $this->formatter->number($activeUmkm),
                'caption' => 'Active Partners',
            ],
        ];
    }

    private function transactionTrend(string $agentId, DashboardPeriod $period): array
    {
        $points = $period->emptyTrendPoints();
        $transactions = Transaction::query()
            ->with('items.tenant')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('transaction_at', [$period->start, $period->end])
            ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->orderBy('transaction_at')
            ->get();

        foreach ($transactions as $transaction) {
            $key = $transaction->transaction_at?->timezone('Asia/Jakarta')->toDateString();

            if (! $key || ! isset($points[$key])) {
                continue;
            }

            $subtotal = (int) $transaction->items
                ->filter(fn (TransactionItem $item): bool => $item->tenant?->agent_user_id === $agentId)
                ->sum('line_total');

            if ($subtotal <= 0) {
                continue;
            }

            $points[$key]['transaction_count']++;
            $points[$key]['revenue'] += $subtotal;
        }

        return $this->trendPayload($period, $points);
    }

    private function trendPayload(DashboardPeriod $period, array $points): array
    {
        return [
            'active_period' => $period->value,
            'date_range_label' => $period->dateRangeLabel(),
            'available_periods' => [
                ['label' => 'Monthly', 'value' => 'monthly'],
                ['label' => 'Weekly', 'value' => 'weekly'],
            ],
            'points' => array_values($points),
        ];
    }

    private function managedAreaCount(string $agentId): int
    {
        return Tenant::query()
            ->where('agent_user_id', $agentId)
            ->join('housing_area_tenant', 'tenants.id', '=', 'housing_area_tenant.tenant_id')
            ->distinct('housing_area_tenant.housing_area_id')
            ->count('housing_area_tenant.housing_area_id');
    }

    private function umkmSpotlight(Collection $tenants, DashboardPeriod $period): array
    {
        return $tenants
            ->map(function (Tenant $tenant) use ($period): ?array {
                $currentRevenue = $this->completedRevenueForTenant($tenant->id, $period->start, $period->end);

                if ($currentRevenue <= 0) {
                    return null;
                }

                $previousRevenue = $this->completedRevenueForTenant($tenant->id, $period->previousStart, $period->previousEnd);

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'initials' => $this->formatter->initials($tenant->name),
                    'category' => $tenant->category,
                    'growth_percentage' => $this->formatter->growthPercentage($currentRevenue, $previousRevenue),
                    'current_revenue' => $currentRevenue,
                    'current_revenue_formatted' => $this->formatter->money($currentRevenue),
                    'detail_url' => null,
                ];
            })
            ->filter()
            ->sortByDesc('growth_percentage')
            ->take(2)
            ->values()
            ->all();
    }

    private function topUmkmCommissions(Collection $tenants): array
    {
        return $tenants
            ->map(function (Tenant $tenant): array {
                $revenue = $this->completedRevenueForTenant($tenant->id);
                $commission = $this->calculator->commissionFromRevenue($revenue);

                return [
                    'umkm' => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'initials' => $this->formatter->initials($tenant->name),
                    ],
                    'store_revenue' => $revenue,
                    'store_revenue_formatted' => $this->formatter->money($revenue),
                    'agent_commission' => $commission,
                    'agent_commission_formatted' => $this->formatter->money($commission),
                ];
            })
            ->sortByDesc('agent_commission')
            ->take(3)
            ->values()
            ->all();
    }

    private function commissionSummary(array $summary): array
    {
        return [
            'total_agent_commission' => (int) $summary['total_commission'],
            'total_agent_commission_formatted' => $this->formatter->money((int) $summary['total_commission']),
            'total_agents' => 1,
            'total_agents_formatted' => '1',
        ];
    }

    private function transactionCount(string $agentId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): int
    {
        return Transaction::query()
            ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->when($start && $end, fn ($query) => $query->whereBetween('transaction_at', [$start, $end]))
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    private function completedRevenueForAgent(string $agentId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): int
    {
        return (int) TransactionItem::query()
            ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->whereHas('transaction', function ($query) use ($start, $end): void {
                $query->where('status', Transaction::STATUS_COMPLETED)
                    ->when($start && $end, fn ($query) => $query->whereBetween('transaction_at', [$start, $end]));
            })
            ->sum('line_total');
    }

    private function completedRevenueForTenant(string $tenantId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): int
    {
        return (int) TransactionItem::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('transaction', function ($query) use ($start, $end): void {
                $query->where('status', Transaction::STATUS_COMPLETED)
                    ->when($start && $end, fn ($query) => $query->whereBetween('transaction_at', [$start, $end]));
            })
            ->sum('line_total');
    }

    private function mapTenant(Tenant $tenant): array
    {
        $completedRevenue = $this->completedRevenueForTenant($tenant->id);
        $transactionCount = Transaction::query()
            ->whereHas('items', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->distinct('transactions.id')
            ->count('transactions.id');
        $commission = $this->calculator->commissionFromRevenue($completedRevenue);

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'seller' => [
                'id' => $tenant->owner?->id,
                'name' => $tenant->owner?->name,
                'email' => $tenant->owner?->email,
                'phone' => $tenant->owner?->phone,
            ],
            'transaction_count' => $transactionCount,
            'total_revenue' => $completedRevenue,
            'total_revenue_label' => $this->formatter->money($completedRevenue),
            'agent_commission' => $commission,
            'agent_commission_label' => $this->formatter->money($commission),
        ];
    }

    private function mapTransaction(Transaction $transaction, string $agentId): array
    {
        $items = $transaction->items
            ->filter(fn (TransactionItem $item): bool => $item->tenant?->agent_user_id === $agentId)
            ->values();
        $agentSubtotal = (int) $items->sum('line_total');

        return [
            'id' => $transaction->id,
            'order_number' => $transaction->order_number,
            'buyer' => [
                'id' => $transaction->user?->id,
                'name' => $transaction->user?->name,
                'email' => $transaction->user?->email,
                'phone' => $transaction->user?->phone,
            ],
            'store_name' => $items->first()?->tenant?->name,
            'umkm' => [
                'id' => $items->first()?->tenant?->id,
                'name' => $items->first()?->tenant?->name,
                'initials' => $this->formatter->initials($items->first()?->tenant?->name),
            ],
            'agent_subtotal_amount' => $agentSubtotal,
            'agent_subtotal_amount_label' => $this->formatter->money($agentSubtotal),
            'total_amount' => $agentSubtotal,
            'total_amount_formatted' => $this->formatter->money($agentSubtotal),
            'agent_commission' => $transaction->status === Transaction::STATUS_COMPLETED ? $this->calculator->commissionFromRevenue($agentSubtotal) : 0,
            'status' => $transaction->status,
            'status_code' => $transaction->statusCode(),
            'status_label' => $this->formatter->statusLabel($transaction->statusCode()),
            'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            'transaction_date_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y'),
            'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    private function withMoneyLabels(array $summary): array
    {
        foreach (['total_revenue', 'total_commission', 'withdrawn_commission', 'available_commission'] as $key) {
            if (is_array($summary[$key] ?? null)) {
                continue;
            }

            $summary[$key.'_label'] = $this->formatter->money((int) $summary[$key]);
        }

        return $summary;
    }
}
