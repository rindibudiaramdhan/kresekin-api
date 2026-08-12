<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerPortalWebTest extends TestCase
{
    public function test_login_portal_exposes_owner_tab_for_future_email_or_phone_accounts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-role-tab="owner"', false)
            ->assertSee('data-email-enabled="true"', false)
            ->assertSee('data-phone-enabled="true"', false)
            ->assertSee('data-default-method="email"', false);
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
