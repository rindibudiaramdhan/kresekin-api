<?php

namespace Tests\Unit;

use App\Support\PromoCodeCatalog;
use PHPUnit\Framework\TestCase;

class PromoCodeCatalogTest extends TestCase
{
    public function test_all_returns_expected_promo_codes(): void
    {
        $promoCodes = PromoCodeCatalog::all();

        $this->assertArrayHasKey(PromoCodeCatalog::HEMAT10, $promoCodes);
        $this->assertArrayHasKey(PromoCodeCatalog::KRESEKIN5K, $promoCodes);
        $this->assertSame('Hemat 10%', $promoCodes[PromoCodeCatalog::HEMAT10]['name']);
        $this->assertSame('percentage', $promoCodes[PromoCodeCatalog::HEMAT10]['discount_type']);
        $this->assertSame(5000, $promoCodes[PromoCodeCatalog::KRESEKIN5K]['discount_value']);
    }

    public function test_codes_returns_all_promo_codes(): void
    {
        $this->assertSame([
            PromoCodeCatalog::HEMAT10,
            PromoCodeCatalog::KRESEKIN5K,
        ], PromoCodeCatalog::codes());
    }

    public function test_find_returns_promo_code_or_null(): void
    {
        $this->assertSame('HEMAT10', PromoCodeCatalog::find('hemat10')['code']);
        $this->assertSame('KRESEKIN5K', PromoCodeCatalog::find(' KRESEKIN5K ')['code']);
        $this->assertNull(PromoCodeCatalog::find('UNKNOWN'));
        $this->assertNull(PromoCodeCatalog::find(null));
    }
}
