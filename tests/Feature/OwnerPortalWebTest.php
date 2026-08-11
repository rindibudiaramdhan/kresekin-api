<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerPortalWebTest extends TestCase
{
    public function test_login_portal_exposes_owner_tab_with_configured_contact_availability(): void
    {
        config()->set('api.owner.email', null);
        config()->set('api.owner.phone', '+628123456789');
        config()->set('api.owner.login_type', 'phone');

        $this->get('/')
            ->assertOk()
            ->assertSee('data-role-tab="owner"', false)
            ->assertSee('data-email-enabled="false"', false)
            ->assertSee('data-phone-enabled="true"', false)
            ->assertSee('data-default-method="phone"', false);
    }

    public function test_owner_monitoring_portal_renders_read_only_dashboard_shell(): void
    {
        $this->get('/owner/online-monitoring')
            ->assertOk()
            ->assertSee('Online Monitoring')
            ->assertSee('Performa Toko')
            ->assertSee('Order Terbaru')
            ->assertSee('/api/owner/online-monitoring/summary', false)
            ->assertDontSee('Ubah Status');
    }
}
