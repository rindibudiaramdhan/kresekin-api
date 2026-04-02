<?php

namespace Tests\Unit;

use App\Support\DeliveryMethodCatalog;
use PHPUnit\Framework\TestCase;

class DeliveryMethodCatalogTest extends TestCase
{
    public function test_all_returns_expected_delivery_methods(): void
    {
        $methods = DeliveryMethodCatalog::all();

        $this->assertArrayHasKey(DeliveryMethodCatalog::STORE_COURIER, $methods);
        $this->assertArrayHasKey(DeliveryMethodCatalog::PICKUP, $methods);
        $this->assertSame('Antar Kurir Toko', $methods[DeliveryMethodCatalog::STORE_COURIER]['name']);
        $this->assertSame(0, $methods[DeliveryMethodCatalog::PICKUP]['fee']);
    }

    public function test_codes_returns_all_delivery_method_codes(): void
    {
        $this->assertSame([
            DeliveryMethodCatalog::STORE_COURIER,
            DeliveryMethodCatalog::PICKUP,
        ], DeliveryMethodCatalog::codes());
    }

    public function test_find_returns_method_or_null(): void
    {
        $this->assertSame('pickup', DeliveryMethodCatalog::find('pickup')['code']);
        $this->assertNull(DeliveryMethodCatalog::find('unknown'));
        $this->assertNull(DeliveryMethodCatalog::find(null));
    }
}
