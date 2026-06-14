<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\Http\JsonResponse;

class GetFinanceDashboardController extends Controller
{
    public function __invoke(FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $recentTransactions = Transaction::query()
            ->with(['items.tenant.owner', 'user'])
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $recentTransactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction));
        $totalTransactions = Transaction::query()->count();
        $totalTransactionAmount = (int) Transaction::query()->sum('total_amount');
        $activeStoreCount = Tenant::query()
            ->whereHas('products')
            ->count();
        $allStoreCount = Tenant::query()->count();
        $mappedRecentTransactions = $recentTransactions
            ->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction))
            ->values();

        return response()->json([
            'message' => 'Dashboard finance berhasil diambil.',
            'data' => [
                'meta' => $this->meta('finance'),
                'summary' => $this->dashboardSummary($totalTransactionAmount, $totalTransactions, $activeStoreCount),
                'transaction_trend' => $this->dummyTransactionTrend(),
                'umkm_spotlight' => $this->dummyUmkmSpotlight(),
                'top_agent_commissions' => $this->dummyTopAgentCommissions(),
                'commission_summary' => $this->dummyCommissionSummary(),
                'total_transactions' => $totalTransactions,
                'total_transaction_amount' => $totalTransactionAmount,
                'total_transaction_amount_label' => $this->moneyLabel($totalTransactionAmount),
                'active_store_count' => $activeStoreCount,
                'all_store_count' => $allStoreCount,
                'recent_transactions' => $mappedRecentTransactions,
            ],
        ]);
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
                'initials' => $this->initials($transaction->items->first()?->tenant?->name),
            ],
            'total_amount' => $transaction->total_amount,
            'total_amount_label' => $this->moneyLabel((int) $transaction->total_amount),
            'total_amount_formatted' => $this->moneyLabel((int) $transaction->total_amount),
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

    private function dashboardSummary(int $totalTransactionAmount, int $totalTransactions, int $activeStoreCount): array
    {
        return [
            'total_umkm_revenue' => [
                'value' => $totalTransactionAmount,
                'formatted' => $this->moneyLabel($totalTransactionAmount),
                'growth_percentage' => 12.5,
            ],
            'total_orders' => [
                'value' => $totalTransactions,
                'formatted' => number_format($totalTransactions, 0, ',', '.'),
                'growth_percentage' => 8.2,
            ],
            'active_umkm' => [
                'value' => $activeStoreCount,
                'formatted' => number_format($activeStoreCount, 0, ',', '.'),
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

    private function dummyCommissionSummary(): array
    {
        return [
            'total_agent_commission' => 482500000,
            'total_agent_commission_formatted' => $this->moneyLabel(482500000),
            'total_agents' => 842,
            'total_agents_formatted' => '842',
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

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
