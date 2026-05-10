<?php

namespace Tests\Unit;

use App\Models\OrderTimeOption;
use PHPUnit\Framework\TestCase;

class OrderTimeOptionTest extends TestCase
{
    public function test_code_is_normalized_to_lowercase(): void
    {
        $option = new OrderTimeOption([
            'code' => ' JADWALKAN ',
        ]);

        $this->assertSame('jadwalkan', $option->code);
    }

    public function test_boolean_and_integer_fields_are_casted(): void
    {
        $option = new OrderTimeOption([
            'requires_schedule' => 1,
            'sort_order' => '2',
            'is_active' => 0,
        ]);

        $this->assertTrue($option->requires_schedule);
        $this->assertSame(2, $option->sort_order);
        $this->assertFalse($option->is_active);
    }
}
