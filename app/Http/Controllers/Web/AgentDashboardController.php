<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\AgentCommissionCalculator;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    public function __invoke(AgentCommissionCalculator $calculator): View
    {
        $agentId = auth()->id();
        $summary = $calculator->summary($agentId);
        $tenants = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agentId)
            ->orderBy('name')
            ->get();

        return view('agent.dashboard', [
            'agentName' => auth()->user()?->name,
            'agentEmail' => auth()->user()?->email,
            'summary' => $summary,
            'sellerCount' => $tenants->pluck('owner_user_id')->filter()->unique()->count(),
            'tenantCount' => $tenants->count(),
            'productCount' => Product::query()
                ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->count(),
            'transactionCount' => Transaction::query()
                ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->distinct('transactions.id')
                ->count('transactions.id'),
            'stores' => $tenants->map(function (Tenant $tenant) use ($calculator): array {
                $revenue = (int) TransactionItem::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
                    ->sum('line_total');

                $transactionCount = Transaction::query()
                    ->whereHas('items', fn ($query) => $query->where('tenant_id', $tenant->id))
                    ->distinct('transactions.id')
                    ->count('transactions.id');

                return [
                    'tenant' => $tenant,
                    'transaction_count' => $transactionCount,
                    'revenue' => $revenue,
                    'commission' => $calculator->commissionFromRevenue($revenue),
                ];
            })->values(),
            'recentTenants' => Tenant::query()->where('agent_user_id', $agentId)->latest()->limit(5)->get(),
            'recentProducts' => Product::query()
                ->with('tenant')
                ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->latest()
                ->limit(5)
                ->get(),
            'recentTransactions' => Transaction::query()
                ->with(['items.tenant', 'user'])
                ->whereHas('items.tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->orderByDesc('transaction_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'agentId' => $agentId,
        ]);
    }
}
