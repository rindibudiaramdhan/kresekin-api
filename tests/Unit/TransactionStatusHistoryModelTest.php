<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class TransactionStatusHistoryModelTest extends TestCase
{
    public function test_it_defines_expected_casts(): void
    {
        $history = new TransactionStatusHistory();
        $history->forceFill([
            'sequence' => '2',
        ]);

        $casts = $history->getCasts();

        $this->assertSame('datetime', $casts['status_at']);
        $this->assertSame('integer', $casts['sequence']);
        $this->assertSame(2, $history->sequence);
    }

    public function test_it_defines_transaction_relation(): void
    {
        $relation = (new TransactionStatusHistory())->transaction();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Transaction::class, $relation->getRelated());
    }
}
