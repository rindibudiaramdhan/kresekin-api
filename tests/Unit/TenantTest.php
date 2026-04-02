<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    public function test_is_open_at_returns_false_when_operating_hours_are_missing(): void
    {
        $tenant = new Tenant();

        $this->assertFalse($tenant->isOpenAt('10:00'));
        $this->assertNull($tenant->operatingHoursLabel());
    }

    public function test_is_open_at_returns_true_within_same_day_operating_hours(): void
    {
        $tenant = new Tenant();
        $tenant->open_time = '07:00';
        $tenant->close_time = '21:00';

        $this->assertTrue($tenant->isOpenAt('10:00'));
        $this->assertFalse($tenant->isOpenAt('22:00'));
        $this->assertSame('Buka 07:00 sd 21:00', $tenant->operatingHoursLabel());
    }

    public function test_is_open_at_supports_overnight_operating_hours(): void
    {
        $tenant = new Tenant();
        $tenant->open_time = '22:00';
        $tenant->close_time = '03:00';

        $this->assertTrue($tenant->isOpenAt('23:30'));
        $this->assertTrue($tenant->isOpenAt('01:15'));
        $this->assertFalse($tenant->isOpenAt('12:00'));
    }

    public function test_category_ui_metadata_returns_defined_metadata(): void
    {
        $metadata = Tenant::categoryUiMetadata(Tenant::CATEGORY_TOILETRIES);

        $this->assertSame('toiletries', $metadata['icon_key']);
        $this->assertSame('#FFF5DF', $metadata['background_color']);
        $this->assertSame('#F4B544', $metadata['icon_color']);
    }

    public function test_category_ui_metadata_returns_fallback_for_unknown_category(): void
    {
        $metadata = Tenant::categoryUiMetadata('Kategori Lain');

        $this->assertSame('kategori_lain', $metadata['icon_key']);
        $this->assertSame('#F3F4F6', $metadata['background_color']);
        $this->assertSame('#6B7280', $metadata['icon_color']);
    }
}
