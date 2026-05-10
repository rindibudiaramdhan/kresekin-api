<?php

namespace Tests\Unit;

use App\Support\OrderTimeOptionCatalog;
use PHPUnit\Framework\TestCase;

class OrderTimeOptionCatalogTest extends TestCase
{
    public function test_all_returns_expected_order_time_options(): void
    {
        $options = OrderTimeOptionCatalog::all();

        $this->assertArrayHasKey(OrderTimeOptionCatalog::NOW, $options);
        $this->assertArrayHasKey(OrderTimeOptionCatalog::SCHEDULED, $options);
        $this->assertSame('Sekarang', $options[OrderTimeOptionCatalog::NOW]['name']);
        $this->assertSame('estimasi 15-30 menit', $options[OrderTimeOptionCatalog::NOW]['description']);
        $this->assertSame('Jadwalkan', $options[OrderTimeOptionCatalog::SCHEDULED]['name']);
    }

    public function test_codes_returns_all_order_time_option_codes(): void
    {
        $this->assertSame([
            OrderTimeOptionCatalog::NOW,
            OrderTimeOptionCatalog::SCHEDULED,
        ], OrderTimeOptionCatalog::codes());
    }

    public function test_find_returns_option_or_null(): void
    {
        $this->assertSame('sekarang', OrderTimeOptionCatalog::find('sekarang')['code']);
        $this->assertNull(OrderTimeOptionCatalog::find('unknown'));
        $this->assertNull(OrderTimeOptionCatalog::find(null));
    }
}
