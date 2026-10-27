<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantShippingSetting;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role): User
    {
        $user = User::query()->create(['name' => 'Shipping User', 'email' => uniqid().'@example.com', 'type' => 'email', 'role' => $role]);
        $token = uniqid('shipping-');
        UserSessionToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $user;
    }

    private function tenant(User $seller): Tenant
    {
        return Tenant::query()->create(['owner_user_id' => $seller->id, 'name' => 'Shipping Store', 'latitude' => 0, 'longitude' => 0]);
    }

    public function test_seller_can_read_replace_and_switch_all_modes(): void
    {
        $tenant = $this->tenant($this->login('seller'));
        $url = '/api/seller/tenants/'.$tenant->id.'/shipping-settings';
        $this->getJson($url)->assertOk()->assertJsonPath('data', null);
        foreach ([
            ['mode' => 'flat', 'flat_fee' => 5000, 'fee_per_km' => null],
            ['mode' => 'distance', 'flat_fee' => null, 'fee_per_km' => 2000],
            ['mode' => 'free', 'flat_fee' => null, 'fee_per_km' => null],
        ] as $payload) {
            $this->putJson($url, $payload)->assertOk()->assertJsonPath('data.mode', $payload['mode']);
            $this->getJson($url)->assertOk()->assertJsonPath('data.flat_fee', $payload['flat_fee'])->assertJsonPath('data.fee_per_km', $payload['fee_per_km']);
        }
        $this->assertDatabaseCount('tenant_shipping_settings', 1);
    }

    public function test_settings_validate_tariffs_and_range(): void
    {
        $tenant = $this->tenant($this->login('seller'));
        $url = '/api/seller/tenants/'.$tenant->id.'/shipping-settings';
        foreach ([
            ['mode' => 'invalid'], ['mode' => 'flat'], ['mode' => 'distance'],
            ['mode' => 'flat', 'flat_fee' => -1], ['mode' => 'flat', 'flat_fee' => 1.5],
            ['mode' => 'free', 'flat_fee' => 5000], ['mode' => 'free', 'max_distance_km' => 0],
        ] as $payload) {
            $this->putJson($url, $payload)->assertUnprocessable();
        }
    }

    public function test_endpoints_enforce_authentication_roles_and_ownership(): void
    {
        $this->getJson('/api/seller/tenants/missing/shipping-settings')->assertUnauthorized();
        $this->putJson('/api/seller/tenants/missing/shipping-settings', ['mode' => 'free'])->assertUnauthorized();
        $this->postJson('/api/tenants/missing/shipping-quote')->assertUnauthorized();
        $tenant = $this->tenant($this->login('seller'));
        $this->postJson('/api/tenants/'.$tenant->id.'/shipping-quote')->assertForbidden();
        $this->login('seller');
        $url = '/api/seller/tenants/'.$tenant->id.'/shipping-settings';
        $this->getJson($url)->assertNotFound();
        $this->putJson($url, ['mode' => 'free'])->assertNotFound();
        $this->login('buyer');
        $this->getJson($url)->assertForbidden();
        $this->putJson($url, ['mode' => 'free'])->assertForbidden();
    }

    public function test_buyer_quotes_all_modes_and_outside_range(): void
    {
        $tenant = $this->tenant($this->login('seller'));
        $settingsUrl = '/api/seller/tenants/'.$tenant->id.'/shipping-settings';
        foreach ([['mode' => 'free'], ['mode' => 'flat', 'flat_fee' => 5000], ['mode' => 'distance', 'fee_per_km' => 2000]] as $payload) {
            $this->putJson($settingsUrl, $payload)->assertOk();
            $sellerHeaders = $this->defaultHeaders;
            $this->login('buyer');
            $response = $this->postJson('/api/tenants/'.$tenant->id.'/shipping-quote', ['latitude' => 0, 'longitude' => 0.01])->assertOk()->assertJsonPath('data.is_deliverable', true);
            $expected = match ($payload['mode']) {
                'free' => 0, 'flat' => 5000, 'distance' => 2224
            };
            $response->assertJsonPath('data.delivery_fee', $expected);
            $this->defaultHeaders = $sellerHeaders;
        }
        $this->putJson($settingsUrl, ['mode' => 'flat', 'flat_fee' => 5000, 'max_distance_km' => 1])->assertOk();
        $this->login('buyer');
        $this->postJson('/api/tenants/'.$tenant->id.'/shipping-quote', ['latitude' => 0, 'longitude' => 0.01])->assertOk()->assertJsonPath('data.is_deliverable', false)->assertJsonPath('data.delivery_fee', null);
    }

    public function test_quote_errors_and_zero_distance(): void
    {
        $tenant = $this->tenant($this->login('seller'));
        $this->login('buyer');
        $url = '/api/tenants/'.$tenant->id.'/shipping-quote';
        $this->postJson($url, ['latitude' => 91, 'longitude' => 181])->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
        $this->postJson($url, ['latitude' => 0, 'longitude' => 0])->assertUnprocessable()->assertJsonValidationErrors('shipping_settings');
        $this->postJson('/api/tenants/missing/shipping-quote', ['latitude' => 0, 'longitude' => 0])->assertNotFound();
        TenantShippingSetting::query()->create(['tenant_id' => $tenant->id, 'mode' => 'distance', 'fee_per_km' => 2000]);
        $this->postJson($url, ['latitude' => 0, 'longitude' => 0])->assertOk()->assertJsonPath('data.delivery_fee', 0);
        $tenant->update(['latitude' => null]);
        $this->postJson($url, ['latitude' => 0, 'longitude' => 0])->assertUnprocessable()->assertJsonValidationErrors('tenant');
    }
}
