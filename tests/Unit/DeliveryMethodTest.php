<?php

namespace Tests\Unit;

use App\Models\DeliveryMethod;
use PHPUnit\Framework\TestCase;

class DeliveryMethodTest extends TestCase
{
    public function test_code_is_normalized_to_lowercase(): void
    {
        $deliveryMethod = new DeliveryMethod([
            'code' => ' STORE_COURIER ',
        ]);

        $this->assertSame('store_courier', $deliveryMethod->code);
    }

    public function test_numeric_and_boolean_fields_are_casted(): void
    {
        $deliveryMethod = new DeliveryMethod([
            'fee' => '2500',
            'requires_order_time' => 1,
            'sort_order' => '2',
            'is_active' => 0,
        ]);

        $this->assertSame(2500, $deliveryMethod->fee);
        $this->assertTrue($deliveryMethod->requires_order_time);
        $this->assertSame(2, $deliveryMethod->sort_order);
        $this->assertFalse($deliveryMethod->is_active);
    }
}
