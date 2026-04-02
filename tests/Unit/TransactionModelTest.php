<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    public function test_it_defines_expected_casts(): void
    {
        $casts = (new Transaction())->getCasts();

        $this->assertSame('datetime', $casts['transaction_at']);
        $this->assertSame('integer', $casts['subtotal_amount']);
        $this->assertSame('integer', $casts['delivery_fee']);
        $this->assertSame('integer', $casts['total_amount']);
    }

    public function test_it_defines_expected_relations(): void
    {
        $transaction = new Transaction();

        $this->assertSame(User::class, get_class($transaction->user()->getRelated()));
        $this->assertSame(TransactionStatusHistory::class, get_class($transaction->statusHistories()->getRelated()));
        $this->assertSame(TransactionItem::class, get_class($transaction->items()->getRelated()));
    }
}
