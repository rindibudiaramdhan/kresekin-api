<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodOption;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_payment_method_code_is_normalized_to_lowercase(): void
    {
        $paymentMethod = new PaymentMethod([
            'code' => ' BANK_TRANSFER ',
        ]);

        $this->assertSame('bank_transfer', $paymentMethod->code);
    }

    public function test_payment_method_option_code_is_normalized_to_lowercase(): void
    {
        $option = new PaymentMethodOption([
            'code' => ' BCA ',
        ]);

        $this->assertSame('bca', $option->code);
    }

    public function test_boolean_and_integer_fields_are_casted(): void
    {
        $paymentMethod = new PaymentMethod([
            'requires_option' => 1,
            'sort_order' => '2',
            'is_active' => 0,
        ]);

        $this->assertTrue($paymentMethod->requires_option);
        $this->assertSame(2, $paymentMethod->sort_order);
        $this->assertFalse($paymentMethod->is_active);
    }
}
