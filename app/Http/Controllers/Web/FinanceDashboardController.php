<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\FinanceDisbursementSyncer;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __invoke(FinanceDisbursementSyncer $syncer): View
    {
        $recentTransactions = Transaction::query()
            ->with(['items.tenant.owner', 'user'])
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentTransactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction));

        return view('finance.dashboard', [
            'totalTransactions' => Transaction::query()->count(),
            'totalTransactionAmount' => (int) Transaction::query()->sum('total_amount'),
            'activeStoreCount' => Tenant::query()->whereHas('products')->count(),
            'allStoreCount' => Tenant::query()->count(),
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
