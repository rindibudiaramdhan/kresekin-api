<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AgentSellerController extends Controller
{
    public function index(Request $request, AgentCommissionCalculator $calculator): View
    {
        $agentId = $request->user()->id;
        $sellers = User::query()
            ->where('role', User::ROLE_SELLER)
            ->whereHas('ownedTenants', fn ($query) => $query->where('agent_user_id', $agentId))
            ->with(['ownedTenants' => fn ($query) => $query->where('agent_user_id', $agentId)->orderBy('name')])
            ->orderBy('name')
            ->paginate(10);

        return view('agent.sellers.index', [
            'sellers' => $sellers,
            'calculator' => $calculator,
        ]);
    }

    public function show(Request $request, string $sellerId, AgentCommissionCalculator $calculator): View
    {
        $agentId = $request->user()->id;
        $seller = User::query()
            ->where('role', User::ROLE_SELLER)
            ->whereHas('ownedTenants', fn ($query) => $query->where('agent_user_id', $agentId))
            ->with(['ownedTenants' => fn ($query) => $query->where('agent_user_id', $agentId)->orderBy('name')])
            ->find($sellerId);

        if (! $seller) {
            throw new NotFoundHttpException('Seller tidak ditemukan.');
        }

        $tenantIds = $seller->ownedTenants->pluck('id')->all();
        $transactions = Transaction::query()
            ->with(['items.tenant', 'user'])
            ->whereHas('items', fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('agent.sellers.show', [
            'seller' => $seller,
            'transactions' => $transactions,
            'calculator' => $calculator,
        ]);
    }

    public static function completedRevenue(array $tenantIds): int
    {
        if ($tenantIds === []) {
            return 0;
        }

        return (int) TransactionItem::query()
            ->whereIn('tenant_id', $tenantIds)
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
            ->sum('line_total');
    }

    public static function transactionCount(array $tenantIds): int
    {
        if ($tenantIds === []) {
            return 0;
        }

        return Transaction::query()
            ->whereHas('items', fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    public static function tenantCompletedRevenue(Tenant $tenant): int
    {
        return self::completedRevenue([$tenant->id]);
    }
}
