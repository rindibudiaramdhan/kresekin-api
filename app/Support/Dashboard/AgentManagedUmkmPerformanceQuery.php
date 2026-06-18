<?php

namespace App\Support\Dashboard;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Support\AgentCommissionCalculator;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AgentManagedUmkmPerformanceQuery
{
    public function __construct(
        private readonly AgentCommissionCalculator $calculator,
        private readonly DashboardFormatter $formatter,
    ) {}

    public function forRequest(User $agent, Request $request): LengthAwarePaginator
    {
        $period = DashboardPeriod::from($request->query('period'));
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $paginator = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agent->id)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (Tenant $tenant): array => $this->mapTenant($tenant, $period))
                ->values()
        );

        return $paginator;
    }

    private function mapTenant(Tenant $tenant, DashboardPeriod $period): array
    {
        $currentRevenue = $this->completedRevenueForTenant($tenant->id, $period->start, $period->end);
        $previousRevenue = $this->completedRevenueForTenant($tenant->id, $period->previousStart, $period->previousEnd);
        $commission = $this->calculator->commissionFromRevenue($currentRevenue);
        $growth = $this->formatter->growthPercentage($currentRevenue, $previousRevenue);
        $status = $this->status($tenant);

        return [
            'id' => $tenant->id,
            'display_id' => $tenant->id,
            'name' => $tenant->name,
            'initials' => $this->formatter->initials($tenant->name),
            'category' => $tenant->category,
            'total_transaction_amount' => $currentRevenue,
            'total_transaction_amount_label' => $this->formatter->money($currentRevenue),
            'growth_percentage' => $growth,
            'growth_label' => $this->growthLabel($growth, $currentRevenue, $previousRevenue),
            'agent_commission' => $commission,
            'agent_commission_label' => $this->formatter->money($commission),
            'status' => $status,
            'status_label' => $status === 'active' ? 'Aktif' : 'Menunggu Aktivasi',
            'detail_url' => null,
        ];
    }

    private function completedRevenueForTenant(string $tenantId, CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) TransactionItem::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('transaction', function ($query) use ($start, $end): void {
                $query->where('status', Transaction::STATUS_COMPLETED)
                    ->whereBetween('transaction_at', [$start, $end]);
            })
            ->sum('line_total');
    }

    private function status(Tenant $tenant): string
    {
        $hasActiveProduct = $tenant->products()
            ->where('is_active', true)
            ->exists();
        $hasCompletedTransaction = Transaction::query()
            ->whereHas('items', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->where('status', Transaction::STATUS_COMPLETED)
            ->exists();

        return $hasActiveProduct || $hasCompletedTransaction ? 'active' : 'pending_activation';
    }

    private function growthLabel(float $growth, int $current, int $previous): string
    {
        if ($previous === 0 && $current > 0) {
            return 'Baru';
        }

        if ($growth > 0) {
            return '+'.number_format($growth, 1, ',', '.').'%';
        }

        return number_format($growth, 1, ',', '.').'%';
    }
}
