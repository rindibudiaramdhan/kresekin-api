<?php

namespace Tests\Unit;

use App\Support\PaymentMethodCatalog;
use PHPUnit\Framework\TestCase;

class PaymentMethodCatalogTest extends TestCase
{
    public function test_all_returns_expected_payment_methods(): void
    {
        $methods = PaymentMethodCatalog::all();

        $this->assertArrayHasKey(PaymentMethodCatalog::BANK_TRANSFER, $methods);
        $this->assertArrayHasKey(PaymentMethodCatalog::QR_PAYMENT, $methods);
        $this->assertArrayHasKey(PaymentMethodCatalog::COD, $methods);
        $this->assertTrue($methods[PaymentMethodCatalog::BANK_TRANSFER]['requires_option']);
        $this->assertCount(4, $methods[PaymentMethodCatalog::BANK_TRANSFER]['options']);
        $this->assertSame([], $methods[PaymentMethodCatalog::COD]['options']);
    }

    public function test_codes_returns_all_payment_method_codes(): void
    {
        $this->assertSame([
            PaymentMethodCatalog::BANK_TRANSFER,
            PaymentMethodCatalog::QR_PAYMENT,
            PaymentMethodCatalog::COD,
        ], PaymentMethodCatalog::codes());
    }

    public function test_find_returns_method_or_null(): void
    {
        $method = PaymentMethodCatalog::find(PaymentMethodCatalog::BANK_TRANSFER);

        $this->assertSame('Transfer Bank', $method['name']);
        $this->assertSame('bca', $method['options'][0]['code']);
        $this->assertNull(PaymentMethodCatalog::find('unknown'));
        $this->assertNull(PaymentMethodCatalog::find(null));
    }
}
