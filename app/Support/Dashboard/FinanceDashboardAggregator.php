<?php

namespace App\Support\Dashboard;

use App\Models\AgentCommissionWithdrawal;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Support\AgentCommissionCalculator;
use App\Support\FinanceDisbursementSyncer;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FinanceDashboardAggregator
{
    public function __construct(
        private readonly AgentCommissionCalculator $calculator,
        private readonly DashboardFormatter $formatter,
        private readonly FinanceDisbursementSyncer $syncer,
    ) {}

    public function aggregate(?string $periodValue): array
    {
        $period = DashboardPeriod::from($periodValue);
        $totalTransactions = Transaction::query()->count();
        $totalTransactionAmount = (int) Transaction::query()->sum('total_amount');
        $activeStoreCount = Tenant::query()->whereHas('products')->count();
        $allStoreCount = Tenant::query()->count();
        $recentTransactions = Transaction::query()
            ->with(['items.tenant.owner', 'user'])
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $recentTransactions->each(fn (Transaction $transaction) => $this->syncer->syncForTransaction($transaction));

        return [
            'meta' => $this->meta('finance', $period),
            'summary' => $this->summary($period, $activeStoreCount),
            'transaction_trend' => $this->transactionTrend($period),
            'umkm_spotlight' => $this->umkmSpotlight(Tenant::query()->orderBy('name')->get(), $period),
            'top_agent_commissions' => $this->topAgentCommissions(),
            'commission_summary' => $this->commissionSummary(),
            'total_transactions' => $totalTransactions,
            'total_transaction_amount' => $totalTransactionAmount,
            'total_transaction_amount_label' => $this->formatter->money($totalTransactionAmount),
            'active_store_count' => $activeStoreCount,
            'all_store_count' => $allStoreCount,
            'recent_transactions' => $recentTransactions
                ->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction))
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

    private function summary(DashboardPeriod $period, int $activeStoreCount): array
    {
        $currentRevenue = $this->transactionRevenue($period->start, $period->end);
        $previousRevenue = $this->transactionRevenue($period->previousStart, $period->previousEnd);
        $currentOrders = $this->transactionCount($period->start, $period->end);
        $previousOrders = $this->transactionCount($period->previousStart, $period->previousEnd);

        return [
            'total_umkm_revenue' => [
                'value' => $currentRevenue,
                'formatted' => $this->formatter->money($currentRevenue),
                'growth_percentage' => $this->formatter->growthPercentage($currentRevenue, $previousRevenue),
            ],
            'total_orders' => [
                'value' => $currentOrders,
                'formatted' => $this->formatter->number($currentOrders),
                'growth_percentage' => $this->formatter->growthPercentage($currentOrders, $previousOrders),
            ],
            'active_umkm' => [
                'value' => $activeStoreCount,
                'formatted' => $this->formatter->number($activeStoreCount),
                'caption' => 'Active Partners',
            ],
        ];
    }

    private function transactionTrend(DashboardPeriod $period): array
    {
        $points = $period->emptyTrendPoints();
        $transactions = Transaction::query()
            ->whereBetween('transaction_at', [$period->start, $period->end])
            ->orderBy('transaction_at')
            ->get();

        foreach ($transactions as $transaction) {
            $key = $transaction->transaction_at?->timezone('Asia/Jakarta')->toDateString();

            if (! $key || ! isset($points[$key])) {
                continue;
            }

            $points[$key]['transaction_count']++;
            $points[$key]['revenue'] += (int) $transaction->total_amount;
        }

        return [
            'active_period' => $period->value,
            'available_periods' => [
                ['label' => '30 Days', 'value' => '30_days'],
                ['label' => '90 Days', 'value' => '90_days'],
            ],
            'points' => array_values($points),
        ];
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

    private function topAgentCommissions(): array
    {
        return User::query()
            ->where('role', User::ROLE_AGENT)
            ->withCount('agentTenants')
            ->get()
            ->map(function (User $agent): array {
                $revenue = $this->completedRevenueForAgent($agent->id);
                $commission = $this->calculator->commissionFromRevenue($revenue);
                [$status, $statusLabel] = $this->agentCommissionStatus($agent->id);

                return [
                    'agent' => [
                        'id' => $agent->id,
                        'name' => $agent->name,
                    ],
                    'managed_umkm_count' => (int) $agent->agent_tenants_count,
                    'managed_umkm_label' => ((int) $agent->agent_tenants_count).' Toko',
                    'store_revenue' => $revenue,
                    'store_revenue_formatted' => $this->formatter->money($revenue),
                    'agent_commission' => $commission,
                    'agent_commission_formatted' => $this->formatter->money($commission),
                    'status' => $status,
                    'status_label' => $statusLabel,
                ];
            })
            ->sortByDesc('agent_commission')
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function agentCommissionStatus(string $agentId): array
    {
        $withdrawal = AgentCommissionWithdrawal::query()
            ->where('agent_user_id', $agentId)
            ->orderByDesc('requested_at')
            ->orderByDesc('created_at')
            ->first();

        return match ($withdrawal?->status) {
            AgentCommissionWithdrawal::STATUS_REJECTED => ['rejected', 'DITOLAK'],
            AgentCommissionWithdrawal::STATUS_REQUESTED => ['processing', 'DIPROSES'],
            AgentCommissionWithdrawal::STATUS_APPROVED,
            AgentCommissionWithdrawal::STATUS_PAID => ['approved', 'BERHASIL'],
            default => ['estimated', 'ESTIMASI'],
        };
    }

    private function commissionSummary(): array
    {
        $revenue = (int) TransactionItem::query()
            ->whereHas('tenant', fn ($query) => $query->whereNotNull('agent_user_id'))
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
            ->sum('line_total');
        $commission = $this->calculator->commissionFromRevenue($revenue);
        $agentCount = User::query()->where('role', User::ROLE_AGENT)->count();

        return [
            'total_agent_commission' => $commission,
            'total_agent_commission_formatted' => $this->formatter->money($commission),
            'total_agents' => $agentCount,
            'total_agents_formatted' => $this->formatter->number($agentCount),
        ];
    }

    private function transactionRevenue(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) Transaction::query()
            ->whereBetween('transaction_at', [$start, $end])
            ->sum('total_amount');
    }

    private function transactionCount(CarbonInterface $start, CarbonInterface $end): int
    {
        return Transaction::query()
            ->whereBetween('transaction_at', [$start, $end])
            ->count();
    }

    private function completedRevenueForAgent(string $agentId): int
    {
        return (int) TransactionItem::query()
            ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
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

    private function mapTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'order_number' => $transaction->order_number,
            'buyer' => [
                'id' => $transaction->user?->id,
                'name' => $transaction->user?->name,
                'email' => $transaction->user?->email,
                'phone' => $transaction->user?->phone,
            ],
            'store_names' => $transaction->items->pluck('tenant.name')->filter()->unique()->values(),
            'umkm' => [
                'id' => $transaction->items->first()?->tenant?->id,
                'name' => $transaction->items->first()?->tenant?->name,
                'initials' => $this->formatter->initials($transaction->items->first()?->tenant?->name),
            ],
            'total_amount' => $transaction->total_amount,
            'total_amount_label' => $this->formatter->money((int) $transaction->total_amount),
            'total_amount_formatted' => $this->formatter->money((int) $transaction->total_amount),
            'status' => $transaction->status,
            'status_code' => $transaction->statusCode(),
            'status_label' => $this->formatter->statusLabel($transaction->statusCode()),
            'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            'transaction_date_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y'),
            'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }
}
