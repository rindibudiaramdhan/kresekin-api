<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCategorySchemaTest extends TestCase
{
    private ?string $postgresSchema = null;

    protected function setUp(): void
    {
        parent::setUp();

        // A separate connection and disposable schema prevent touching application tables.
        if ($url = getenv('K02_TEST_PGSQL_URL')) {
            $this->postgresSchema = 'k02_'.str_replace('-', '', (string) Str::uuid());
            config(['database.connections.k02' => array_replace(config('database.connections.pgsql'), [
                'url' => $url,
                'search_path' => $this->postgresSchema,
            ]), 'database.default' => 'k02']);
            DB::statement('CREATE SCHEMA '.$this->postgresSchema);
            DB::statement('SET search_path TO '.$this->postgresSchema);
        } else {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
        }

        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->postgresSchema !== null) {
                DB::statement('DROP SCHEMA '.$this->postgresSchema.' CASCADE');
                DB::purge('k02');
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_upgrade_preserves_legacy_values_and_allows_nullable_categories(): void
    {
        $migration = $this->migration();
        $migration->down();
        $category = $this->category();
        $tenant = Tenant::query()->create(['name' => 'Legacy', 'category' => 'Legacy tenant', 'product_category_id' => $category->id]);
        $product = $this->product($tenant);
        $deleted = $this->product($tenant);
        $deleted->delete();

        $migration->up();

        $this->assertSame('Legacy tenant', $tenant->fresh()->category);
        $this->assertSame($category->id, $tenant->fresh()->product_category_id);
        $this->assertSame('Legacy product', $product->fresh()->category);
        $this->assertNull($product->fresh()->product_category_id);
        $this->assertSame('Legacy product', Product::withTrashed()->findOrFail($deleted->id)->category);
        $this->assertSame(2, Product::withTrashed()->count());
        $this->assertNull(Tenant::query()->create(['name' => 'New tenant'])->fresh()->category);
        $this->assertTrue(Schema::hasIndex('products', ['product_category_id']));
        $product->update(['product_category_id' => $category->id]);
        $this->assertTrue($product->fresh()->productCategory->is($category));
    }

    public function test_orphan_category_is_rejected(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Shop']);
        $this->expectException(QueryException::class);
        $this->product($tenant, (string) Str::uuid());
    }

    public function test_master_deletion_is_rejected_for_active_product(): void
    {
        $this->assertMasterDeletionRejected(false);
    }

    public function test_master_deletion_is_rejected_for_soft_deleted_product(): void
    {
        $this->assertMasterDeletionRejected(true);
    }

    public function test_reseeding_preserves_master_ids_and_product_references(): void
    {
        $category = ProductCategory::query()->create(['name' => 'Old Sayur', 'slug' => 'sayur', 'image_path' => 'old.svg']);
        $tenant = Tenant::query()->create(['name' => 'Shop']);
        $product = $this->product($tenant, $category->id);
        $product->delete();
        $this->seed(ProductCategorySeeder::class);
        $ids = ProductCategory::query()->orderBy('slug')->pluck('id', 'slug')->all();
        $this->seed(ProductCategorySeeder::class);
        $this->assertSame($ids, ProductCategory::query()->orderBy('slug')->pluck('id', 'slug')->all());
        $this->assertCount(15, $ids);
        $this->assertSame($category->id, $ids['sayur']);
        $this->assertSame('Sayur', $category->fresh()->name);
        $this->assertSame($category->id, Product::withTrashed()->findOrFail($product->id)->productCategory->id);
    }

    public function test_rollback_restores_legacy_schema_and_values(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Shop', 'category' => 'Legacy tenant']);
        $product = $this->product($tenant, $this->category()->id);
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('products', 'product_category_id'));
        $this->assertSame('Legacy product', $product->fresh()->category);
        $this->assertSame('Legacy tenant', $tenant->fresh()->category);
        $this->expectException(QueryException::class);
        Tenant::query()->create(['name' => 'Missing category']);
    }

    public function test_rollback_refuses_null_tenants_before_removing_product_references(): void
    {
        Tenant::query()->create(['name' => 'New tenant']);
        try {
            $this->migration()->down();
            $this->fail('Rollback should refuse nullable tenant data.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tenants.category', $exception->getMessage());
            $this->assertTrue(Schema::hasColumn('products', 'product_category_id'));
        }
    }

    private function assertMasterDeletionRejected(bool $softDeleted): void
    {
        $category = $this->category();
        $tenant = Tenant::query()->create(['name' => 'Shop']);
        $product = $this->product($tenant, $category->id);
        if ($softDeleted) {
            $product->delete();
        }
        $this->expectException(QueryException::class);
        $category->delete();
    }

    private function category(): ProductCategory
    {
        return ProductCategory::query()->create(['name' => 'Sayur', 'slug' => 'sayur', 'image_path' => 'sayur.svg']);
    }

    private function product(Tenant $tenant, ?string $categoryId = null): Product
    {
        $attributes = ['tenant_id' => $tenant->id, 'name' => 'Product', 'category' => 'Legacy product', 'price' => 1000];
        if ($categoryId !== null) {
            $attributes['product_category_id'] = $categoryId;
        }

        return Product::query()->create($attributes);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_04_000001_expand_product_category_schema.php');
    }
}
