<?php

namespace Tests\Feature;

use App\Http\Requests\AgentTenantRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Database\Seeders\ProductUnitSeeder;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TenantCategoryRemovalTest extends TestCase
{
    // Exercise schema rebuilds outside transactions, as SQLite migrations require.
    use DatabaseMigrations;

    public function test_upgrade_removes_legacy_columns_and_preserves_tenant_and_products(): void
    {
        $migration = require database_path('migrations/2026_10_07_000002_drop_category_columns_from_tenants.php');
        $migration->down();

        $category = ProductCategory::query()->create(['name' => 'Sayur', 'slug' => 'sayur', 'image_path' => 'images/sayur.svg']);
        $tenant = Tenant::query()->create(['name' => 'Tenant Lama']);
        DB::table('tenants')->where('id', $tenant->id)->update([
            'category' => $category->name,
            'product_category_id' => $category->id,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam',
            'category' => $category->name,
            'price' => 8000,
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('tenants', 'category'));
        $this->assertFalse(Schema::hasColumn('tenants', 'product_category_id'));
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Tenant Lama']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category' => 'Sayur']);
        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);

        $migration->down();
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'category' => null,
            'product_category_id' => null,
        ]);
        $migration->up();
    }

    public function test_tenant_updates_ignore_legacy_category_attributes(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Nama Lama']);
        $payload = [
            'name' => 'Nama Baru',
            'category' => ['ignored'],
            'category_id' => 'ignored',
            'product_category_id' => 'ignored',
        ];
        $validator = Validator::make($payload, (new AgentTenantRequest)->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame(['name' => 'Nama Baru'], $validator->validated());

        $tenant->update($payload);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Nama Baru']);
        $this->assertArrayNotHasKey('category', $tenant->fresh()->getAttributes());
        $this->assertArrayNotHasKey('product_category_id', $tenant->fresh()->getAttributes());
    }

    public function test_tenant_seeder_can_create_and_update_tenants_without_category_columns(): void
    {
        $this->seed(ProductUnitSeeder::class);
        $this->seed(TenantSeeder::class);
        $this->seed(TenantSeeder::class);

        $this->assertDatabaseCount('tenants', 4);
        $this->assertDatabaseCount('products', 13);
        $this->assertDatabaseHas('products', ['name' => 'Bayam Hijau', 'category' => 'Sayur']);
    }
}
