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
use Symfony\Component\HttpFoundation\Response;

class GetAgentSellerDetailController extends Controller
{
    public function __invoke(Request $request, int $sellerId, AgentCommissionCalculator $calculator): JsonResponse
    {
        $agentId = $request->user()->id;
        $seller = User::query()
            ->where('role', User::ROLE_SELLER)
            ->whereHas('ownedTenants', fn ($query) => $query->where('agent_user_id', $agentId))
            ->with(['ownedTenants' => fn ($query) => $query->where('agent_user_id', $agentId)->orderBy('name')])
            ->find($sellerId);

        if (! $seller) {
            return response()->json([
                'message' => 'Seller tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $tenantIds = $seller->ownedTenants->pluck('id')->all();
        $completedRevenue = $this->completedRevenue($tenantIds);

        return response()->json([
            'message' => 'Detail seller agent berhasil diambil.',
            'data' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'tenant_count' => $seller->ownedTenants->count(),
                'transaction_count' => $this->transactionCount($tenantIds),
                'total_revenue' => $completedRevenue,
                'total_revenue_label' => $this->moneyLabel($completedRevenue),
                'agent_commission' => $calculator->commissionFromRevenue($completedRevenue),
                'agent_commission_label' => $this->moneyLabel($calculator->commissionFromRevenue($completedRevenue)),
                'tenants' => $seller->ownedTenants->map(fn (Tenant $tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'category' => $tenant->category,
                    'profile_picture_url' => $tenant->profile_picture_url,
                    'transaction_count' => $this->transactionCount([$tenant->id]),
                    'total_revenue' => $this->completedRevenue([$tenant->id]),
                    'total_revenue_label' => $this->moneyLabel($this->completedRevenue([$tenant->id])),
                    'agent_commission' => $calculator->commissionFromRevenue($this->completedRevenue([$tenant->id])),
                    'agent_commission_label' => $this->moneyLabel($calculator->commissionFromRevenue($this->completedRevenue([$tenant->id]))),
                ])->values(),
            ],
        ]);
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
