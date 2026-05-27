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

        return response()->json([
            'message' => 'Dashboard agent berhasil diambil.',
            'data' => [
                'agent' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                ],
                'summary' => $this->withMoneyLabels($summary),
                'seller_count' => $tenants->pluck('owner_user_id')->filter()->unique()->count(),
                'tenant_count' => $tenants->count(),
                'transaction_count' => $this->transactionCount($agentId),
                'stores' => $tenants->map(fn (Tenant $tenant): array => $this->mapTenant($tenant, $calculator))->values(),
                'recent_transactions' => $recentTransactions->map(fn (Transaction $transaction): array => $this->mapTransaction($transaction, $agentId, $calculator))->values(),
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
            'agent_subtotal_amount' => $agentSubtotal,
            'agent_subtotal_amount_label' => $this->moneyLabel($agentSubtotal),
            'agent_commission' => $transaction->status === Transaction::STATUS_COMPLETED ? $calculator->commissionFromRevenue($agentSubtotal) : 0,
            'status' => $transaction->status,
            'status_code' => $transaction->statusCode(),
            'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            'transaction_at_label' => $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
        ];
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
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
