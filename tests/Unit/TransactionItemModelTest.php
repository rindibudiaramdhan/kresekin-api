<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Tests\TestCase;

class TransactionItemModelTest extends TestCase
{
    public function test_it_defines_expected_casts(): void
    {
        $casts = (new TransactionItem())->getCasts();

        $this->assertSame('integer', $casts['quantity']);
        $this->assertSame('integer', $casts['unit_price']);
        $this->assertSame('integer', $casts['line_total']);
    }

    public function test_it_defines_expected_relations(): void
    {
        $item = new TransactionItem();

        $this->assertSame(Transaction::class, get_class($item->transaction()->getRelated()));
        $this->assertSame(Product::class, get_class($item->product()->getRelated()));
        $this->assertSame(Tenant::class, get_class($item->tenant()->getRelated()));
    }
}
