<?php

namespace App\Support;

use App\Models\AgentCommissionWithdrawal;
use App\Models\Transaction;
use App\Models\TransactionItem;

class AgentCommissionCalculator
{
    public function summary(string $agentId): array
    {
        $revenue = $this->completedRevenue($agentId);
        $commission = $this->commissionFromRevenue($revenue);
        $lockedWithdrawal = $this->lockedWithdrawalAmount($agentId);

        return [
            'commission_rate' => $this->commissionRate(),
            'total_revenue' => $revenue,
            'total_commission' => $commission,
            'withdrawn_commission' => $lockedWithdrawal,
            'available_commission' => max(0, $commission - $lockedWithdrawal),
        ];
    }

    public function completedRevenue(string $agentId): int
    {
        return (int) TransactionItem::query()
            ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
            ->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_COMPLETED))
            ->sum('line_total');
    }

    public function commissionFromRevenue(int $revenue): int
    {
        return (int) round($revenue * $this->commissionRate());
    }

    public function lockedWithdrawalAmount(string $agentId): int
    {
        return (int) AgentCommissionWithdrawal::query()
            ->where('agent_user_id', $agentId)
            ->whereIn('status', AgentCommissionWithdrawal::lockedStatuses())
            ->sum('amount');
    }

    public function commissionRate(): float
    {
        return (float) config('api.agent_commission_rate', 0.05);
    }
}
