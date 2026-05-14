<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentSellerListController extends Controller
{
    public function __invoke(Request $request, AgentCommissionCalculator $calculator): JsonResponse
    {
        $agentId = $request->user()->id;
        $sellers = User::query()
            ->where('role', User::ROLE_SELLER)
            ->whereHas('ownedTenants', fn ($query) => $query->where('agent_user_id', $agentId))
            ->with(['ownedTenants' => fn ($query) => $query->where('agent_user_id', $agentId)->orderBy('name')])
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar seller agent berhasil diambil.',
            'data' => $sellers->getCollection()
                ->map(fn (User $seller): array => $this->mapSeller($seller, $agentId, $calculator))
                ->values(),
            'meta' => [
                'current_page' => $sellers->currentPage(),
                'per_page' => $sellers->perPage(),
                'last_page' => $sellers->lastPage(),
                'total' => $sellers->total(),
                'from' => $sellers->firstItem(),
                'to' => $sellers->lastItem(),
            ],
            'links' => [
                'first' => $sellers->url(1),
                'last' => $sellers->url($sellers->lastPage()),
                'prev' => $sellers->previousPageUrl(),
                'next' => $sellers->nextPageUrl(),
            ],
        ]);
    }

    private function mapSeller(User $seller, int $agentId, AgentCommissionCalculator $calculator): array
    {
        $tenantIds = $seller->ownedTenants->pluck('id');
        $completedRevenue = $this->completedRevenue($tenantIds->all());

        return [
            'id' => $seller->id,
            'name' => $seller->name,
            'email' => $seller->email,
            'phone' => $seller->phone,
            'tenant_count' => $seller->ownedTenants->count(),
            'transaction_count' => $this->transactionCount($tenantIds->all()),
            'total_revenue' => $completedRevenue,
            'total_revenue_label' => $this->moneyLabel($completedRevenue),
            'agent_commission' => $calculator->commissionFromRevenue($completedRevenue),
            'agent_commission_label' => $this->moneyLabel($calculator->commissionFromRevenue($completedRevenue)),
            'tenants' => $seller->ownedTenants
                ->filter(fn (Tenant $tenant): bool => $tenant->agent_user_id === $agentId)
                ->map(fn (Tenant $tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'category' => $tenant->category,
                    'profile_picture_url' => $tenant->profile_picture_url,
                ])
                ->values(),
        ];
    }

    private function completedRevenue(array $tenantIds): int
    {
        if ($tenantIds === []) {
            return 0;
        }

        return (int) TransactionItem::query()
            ->whereIn('tenant_id', $tenantIds)
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
            ->sum('line_total');
    }

    private function transactionCount(array $tenantIds): int
    {
        if ($tenantIds === []) {
            return 0;
        }

        return Transaction::query()
            ->whereHas('items', fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
