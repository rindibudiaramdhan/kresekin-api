<?php

namespace Tests\Feature;

use App\Models\HousingArea;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerAreaFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_follow_user_areas_and_cannot_be_expanded_by_query_parameters(): void
    {
        $areaA = HousingArea::query()->create(['name' => 'Area A', 'code' => 'A']);
        $areaB = HousingArea::query()->create(['name' => 'Area B', 'code' => 'B']);
        $areaC = HousingArea::query()->create(['name' => 'Area C', 'code' => 'C']);
        $user = User::factory()->create(['housing_area_id' => $areaA->id]);
        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'buyer-area-token'),
            'expires_at' => now()->addDay(),
        ]);
        $this->withHeader('Authorization', 'Bearer buyer-area-token');

        foreach (['A' => [$areaA->id], 'B' => [$areaB->id], 'AB' => [$areaA->id, $areaB->id], 'C' => [$areaC->id], 'None' => []] as $name => $areas) {
            $tenant = Tenant::query()->create(['name' => $name]);
            $tenant->housingAreas()->sync($areas);
            Product::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'category' => Tenant::CATEGORY_VEGETABLES,
                'price' => 1000,
                'weight_label' => '1kg',
                'description' => 'Barang uji',
                'delivery_estimate' => 'Hari ini',
            ]);
        }

        foreach (['/api/products', '/api/tenants'] as $endpoint) {
            // Legacy profiles containing only housing_area_id are still supported.
            $this->assertNames($endpoint, ['A', 'AB']);
            $this->assertNames($endpoint.'?housing_area_id='.$areaB->id, []);
        }

        $user->housingAreas()->sync([$areaA->id, $areaB->id]);
        foreach (['/api/products', '/api/tenants'] as $endpoint) {
            $this->assertNames($endpoint, ['A', 'B', 'AB']);
            $this->assertNames($endpoint.'?housing_area_id='.$areaB->id, ['B', 'AB']);
            $this->assertNames($endpoint.'?housing_area_id='.$areaC->id, []);
        }

        // Results must follow the current authenticated user, including after an area change.
        $user->housingAreas()->sync([$areaC->id]);
        $user->update(['housing_area_id' => $areaC->id]);
        foreach (['/api/products', '/api/tenants'] as $endpoint) {
            $this->assertNames($endpoint, ['C']);
        }

        $user->housingAreas()->detach();
        $user->update(['housing_area_id' => null]);
        foreach (['/api/products', '/api/tenants'] as $endpoint) {
            $this->assertNames($endpoint, []);
            $this->assertNames($endpoint.'?housing_area_id='.$areaA->id, []);
        }
    }

    private function assertNames(string $url, array $expected): void
    {
        $response = $this->getJson($url)->assertOk()->assertJsonPath('meta.total', count($expected));
        $this->assertEqualsCanonicalizing($expected, array_column($response->json('data'), 'name'));
    }
}
