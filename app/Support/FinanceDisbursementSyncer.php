<?php

namespace App\Support;

use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Collection;

class FinanceDisbursementSyncer
{
    /**
     * @return Collection<int, FinanceTransactionDisbursement>
     */
    public function syncForTransaction(Transaction $transaction): Collection
    {
        $transaction->loadMissing('items.tenant');

        return $transaction->items
            ->filter(fn (TransactionItem $item): bool => $item->tenant_id !== null && $item->tenant !== null)
            ->groupBy('tenant_id')
            ->map(function (Collection $items) use ($transaction): FinanceTransactionDisbursement {
                /** @var TransactionItem $firstItem */
                $firstItem = $items->first();
                $amount = (int) $items->sum('line_total');

                return FinanceTransactionDisbursement::query()->updateOrCreate(
                    [
                        'transaction_id' => $transaction->id,
                        'tenant_id' => $firstItem->tenant_id,
                    ],
                    [
                        'seller_user_id' => $firstItem->tenant?->owner_user_id,
                        'unique_code' => $this->uniqueCode($transaction, (int) $firstItem->tenant_id),
                        'amount' => $amount,
                    ],
                );
            })
            ->values();
    }

    public function uniqueCode(Transaction $transaction, int $tenantId): string
    {
        return sprintf('FIN-%s-%04d', $transaction->order_number, $tenantId);
    }
}
