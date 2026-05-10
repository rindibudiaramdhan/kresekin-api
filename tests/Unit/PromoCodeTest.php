<?php

namespace Tests\Unit;

use App\Models\PromoCode;
use PHPUnit\Framework\TestCase;

class PromoCodeTest extends TestCase
{
    public function test_code_is_normalized_to_uppercase(): void
    {
        $promoCode = new PromoCode([
            'code' => ' hemat10 ',
        ]);

        $this->assertSame('HEMAT10', $promoCode->code);
    }

    public function test_remaining_quantity_returns_null_for_unlimited_quantity(): void
    {
        $promoCode = new PromoCode([
            'quantity' => null,
            'used_quantity' => 10,
        ]);

        $this->assertNull($promoCode->remainingQuantity());
    }

    public function test_remaining_quantity_never_returns_negative_value(): void
    {
        $promoCode = new PromoCode([
            'quantity' => 10,
            'used_quantity' => 12,
        ]);

        $this->assertSame(0, $promoCode->remainingQuantity());
    }
}
