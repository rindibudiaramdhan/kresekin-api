<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentDashboardController extends Controller
{
    public function __invoke(Request $request, AgentCommissionCalculator $calculator): JsonResponse
    {
        $agentId = $request->user()->id;
        $summary = $calculator->summary($agentId);

        $tenants = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agentId)
            ->orderBy('name')
            ->get();

        $recentTransactions = Transaction::query()
            ->with(['items.tenant', 'user'])
            ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $transactionCount = $this->transactionCount($agentId);
        $sellerCount = $tenants->pluck('owner_user_id')->filter()->unique()->count();
        $mappedRecentTransactions = $recentTransactions
            ->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction, $agentId, $calculator))
            ->values();

        return response()->json([
            'message' => 'Dashboard agent berhasil diambil.',
            'data' => [
                'meta' => $this->meta('agent'),
                'agent' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                ],
                'summary' => $this->dashboardSummary($summary, $transactionCount, $tenants->count()),
                'transaction_trend' => $this->dummyTransactionTrend(),
                'umkm_spotlight' => $this->dummyUmkmSpotlight(),
                'top_agent_commissions' => $this->dummyTopAgentCommissions(),
                'commission_summary' => $this->commissionSummary($summary, $sellerCount),
                'seller_count' => $sellerCount,
                'tenant_count' => $tenants->count(),
                'transaction_count' => $transactionCount,
                'stores' => $tenants->map(fn (Tenant $tenant): array => $this->mapTenant($tenant, $calculator))->values(),
                'recent_transactions' => $mappedRecentTransactions,
            ],
        ]);
    }

    private function transactionCount(string $agentId): int
    {
        return Transaction::query()
            ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    private function mapTenant(Tenant $tenant, AgentCommissionCalculator $calculator): array
    {
        $completedRevenue = (int) TransactionItem::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
            ->sum('line_total');

        $transactionCount = Transaction::query()
            ->whereHas('items', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->distinct('transactions.id')
            ->count('transactions.id');

        $commission = $calculator->commissionFromRevenue($completedRevenue);

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
            'total_revenue_label' => $this->moneyLabel($completedRevenue),
            'agent_commission' => $commission,
            'agent_commission_label' => $this->moneyLabel($commission),
        ];
    }

    private function mapTransaction(Transaction $transaction, string $agentId, AgentCommissionCalculator $calculator): array
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
                'initials' => $this->initials($items->first()?->tenant?->name),
            ],
            'agent_subtotal_amount' => $agentSubtotal,
            'agent_subtotal_amount_label' => $this->moneyLabel($agentSubtotal),
            'total_amount' => $agentSubtotal,
            'total_amount_formatted' => $this->moneyLabel($agentSubtotal),
            'agent_commission' => $transaction->status === Transaction::STATUS_COMPLETED ? $calculator->commissionFromRevenue($agentSubtotal) : 0,
            'status' => $transaction->status,
            'status_code' => $transaction->statusCode(),
            'status_label' => $this->statusLabel($transaction->statusCode()),
            'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            'transaction_date_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y'),
            'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    private function meta(string $role): array
    {
        return [
            'role' => $role,
            'period' => '30_days',
            'generated_at' => now()->toIso8601String(),
            'source' => 'dummy_dashboard_aggregate_v1',
        ];
    }

    private function dashboardSummary(array $summary, int $transactionCount, int $tenantCount): array
    {
        return [
            ...$this->withMoneyLabels($summary),
            'total_umkm_revenue' => [
                'value' => (int) $summary['total_revenue'],
                'formatted' => $this->moneyLabel((int) $summary['total_revenue']),
                'growth_percentage' => 12.5,
            ],
            'total_orders' => [
                'value' => $transactionCount,
                'formatted' => number_format($transactionCount, 0, ',', '.'),
                'growth_percentage' => 8.2,
            ],
            'active_umkm' => [
                'value' => $tenantCount,
                'formatted' => number_format($tenantCount, 0, ',', '.'),
                'caption' => 'Active Partners',
            ],
        ];
    }

    private function dummyTransactionTrend(): array
    {
        return [
            'active_period' => '30_days',
            'available_periods' => [
                ['label' => '30 Days', 'value' => '30_days'],
                ['label' => '90 Days', 'value' => '90_days'],
            ],
            'points' => [
                ['date' => '2024-05-01', 'label' => '01 May', 'transaction_count' => 120, 'revenue' => 1200000],
                ['date' => '2024-05-07', 'label' => '07 May', 'transaction_count' => 165, 'revenue' => 1800000],
                ['date' => '2024-05-14', 'label' => '14 May', 'transaction_count' => 280, 'revenue' => 2600000],
                ['date' => '2024-05-21', 'label' => '21 May', 'transaction_count' => 310, 'revenue' => 3100000],
                ['date' => '2024-05-28', 'label' => '28 May', 'transaction_count' => 210, 'revenue' => 2200000],
                ['date' => '2024-05-30', 'label' => '30 May', 'transaction_count' => 520, 'revenue' => 4800000],
            ],
        ];
    }

    private function dummyUmkmSpotlight(): array
    {
        return [
            [
                'id' => null,
                'name' => 'Tokoma',
                'initials' => 'T',
                'category' => 'Sayur',
                'growth_percentage' => 240,
                'detail_url' => null,
            ],
            [
                'id' => null,
                'name' => 'Sembako Tetangga',
                'initials' => 'ST',
                'category' => 'Retail',
                'growth_percentage' => 185,
                'detail_url' => null,
            ],
        ];
    }

    private function dummyTopAgentCommissions(): array
    {
        return [
            $this->topAgentCommissionRow('agent-xyz', 'Agent XYZ', 42, 285000, 142500, 'approved', 'BERHASIL'),
            $this->topAgentCommissionRow('denny-caknan', 'Denny Caknan', 38, 242000, 12100, 'processing', 'DIPROSES'),
            $this->topAgentCommissionRow('lukas-pesek', 'Lukas Pesek', 31, 198500, 992500, 'rejected', 'DITOLAK'),
        ];
    }

    private function topAgentCommissionRow(string $id, string $name, int $managedUmkm, int $storeRevenue, int $commission, string $status, string $statusLabel): array
    {
        return [
            'agent' => [
                'id' => $id,
                'name' => $name,
            ],
            'managed_umkm_count' => $managedUmkm,
            'managed_umkm_label' => $managedUmkm.' Toko',
            'store_revenue' => $storeRevenue,
            'store_revenue_formatted' => $this->moneyLabel($storeRevenue),
            'agent_commission' => $commission,
            'agent_commission_formatted' => $this->moneyLabel($commission),
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    private function commissionSummary(array $summary, int $sellerCount): array
    {
        return [
            'total_agent_commission' => (int) $summary['total_commission'],
            'total_agent_commission_formatted' => $this->moneyLabel((int) $summary['total_commission']),
            'total_agents' => max(1, $sellerCount),
            'total_agents_formatted' => number_format(max(1, $sellerCount), 0, ',', '.'),
        ];
    }

    private function initials(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    private function statusLabel(?string $statusCode): string
    {
        return match ($statusCode) {
            Transaction::STATUS_CODE_COMPLETED => 'Success',
            Transaction::STATUS_CODE_PENDING_PAYMENT,
            Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
            Transaction::STATUS_CODE_PROCESSING,
            Transaction::STATUS_CODE_ON_THE_WAY => 'Pending',
            Transaction::STATUS_CODE_CANCELED => 'Failed',
            default => 'Pending',
        };
    }

    private function withMoneyLabels(array $summary): array
    {
        foreach (['total_revenue', 'total_commission', 'withdrawn_commission', 'available_commission'] as $key) {
            $summary[$key.'_label'] = $this->moneyLabel((int) $summary[$key]);
        }

        return $summary;
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
