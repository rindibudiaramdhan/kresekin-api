<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Support\ProductCategoryBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ProductCategoryBackfillTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $category;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->category = ProductCategory::query()->create(['name' => 'Sayur Segar', 'slug' => 'sayur-segar', 'image_path' => 'images/category.svg']);
        $this->tenant = Tenant::query()->create(['name' => 'Toko', 'category' => 'Tenant category']);
    }

    private function product(string $category, ?string $id = null): Product
    {
        return Product::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Tidak boleh masuk laporan', 'category' => $category, 'product_category_id' => $id, 'price' => 1000]);
    }

    private function state(string $run): array
    {
        return json_decode(Storage::disk('s3')->get('product-category-backfill/'.$run.'/checkpoint.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_dry_run_inventories_all_records_and_distinguishes_mapping_outcomes(): void
    {
        $exact = $this->product('Sayur Segar');
        $normalized = $this->product("  SAYUR \t SEGAR ");
        $normalized->delete();
        $this->product('Tidak ada');
        $this->product('Sayur Segar', $this->category->id);
        $run = (string) Str::uuid();
        $this->artisan('products:backfill-categories', ['--run' => $run, '--batch' => 2])->assertSuccessful();
        $state = $this->state($run);
        $this->assertTrue($state['completed']);
        $this->assertSame(['matched' => 2, 'unmatched' => 1, 'valid_id' => 1], $state['counts']);
        $this->assertSame(4, $state['after']['total']);
        $this->assertSame(3, $state['after']['null']);
        $this->assertSame(1, $state['after']['soft_deleted']);
        $this->assertNull($exact->fresh()->product_category_id);
        foreach (Storage::disk('s3')->allFiles('product-category-backfill/'.$run) as $path) {
            $this->assertStringNotContainsString('Tidak boleh masuk laporan', Storage::disk('s3')->get($path));
        }
        ProductCategory::query()->create(['name' => 'SAYUR  SEGAR', 'slug' => 'ambiguous', 'image_path' => 'images/category.svg']);
        $plan = app(ProductCategoryBackfill::class)->plan($normalized, ProductCategory::all(), []);
        $this->assertSame('ambiguous', $plan['status']);
        $this->assertCount(2, $plan['candidate_ids']);
    }

    public function test_apply_supports_mapping_soft_delete_batch_resume_and_safe_rerun(): void
    {
        $one = $this->product('Sayur Segar');
        $two = $this->product('Old name');
        $two->delete();
        $valid = $this->product('Do not overwrite', $this->category->id);
        Storage::disk('s3')->put('approved-mapping.json', json_encode(['Old name' => $this->category->id]));
        $run = (string) Str::uuid();
        $options = ['--run' => $run, '--apply' => true, '--batch' => 1, '--mapping' => 'approved-mapping.json'];
        $this->artisan('products:backfill-categories', $options + ['--max-batches' => 1])->assertSuccessful();
        $this->assertFalse($this->state($run)['completed']);
        $this->assertSame($one->id, $this->state($run)['cursor']);
        $this->artisan('products:backfill-categories', $options)->assertSuccessful();
        $this->assertTrue($this->state($run)['completed']);
        $this->assertSame(0, $this->state($run)['after']['null']);
        $this->assertSame(0, $this->state($run)['after']['orphan']);
        $this->assertSame($this->category->id, Product::withTrashed()->findOrFail($two->id)->product_category_id);
        $this->assertSame('Do not overwrite', $valid->fresh()->category);
        $this->artisan('products:backfill-categories', array_replace($options, ['--run' => (string) Str::uuid()]))->assertSuccessful();
        $this->assertSame('Do not overwrite', $valid->fresh()->category);
    }

    public function test_conditional_update_does_not_overwrite_concurrent_text_or_id_edits(): void
    {
        $backfill = app(ProductCategoryBackfill::class);
        $snapshot = $this->product('Sayur Segar');
        DB::table('products')->where('id', $snapshot->id)->update(['category' => 'Concurrent text']);
        $this->assertFalse($backfill->apply($snapshot, $this->category->id, $this->category->name));
        $this->assertSame('Concurrent text', $snapshot->fresh()->category);
        $snapshot2 = $this->product('Sayur Segar');
        DB::table('products')->where('id', $snapshot2->id)->update(['product_category_id' => $this->category->id]);
        $this->assertFalse($backfill->apply($snapshot2, $this->category->id, 'Wrong text'));
        $this->assertSame('Sayur Segar', $snapshot2->fresh()->category);
    }

    public function test_orphan_id_is_reported_without_silent_repair(): void
    {
        $product = $this->product('Sayur Segar');
        // Simulate legacy corruption that cannot be inserted while the FK is enforced.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $invalid = (string) Str::uuid();
        DB::table('products')->where('id', $product->id)->update(['product_category_id' => $invalid]);
        $run = (string) Str::uuid();
        $this->artisan('products:backfill-categories', ['--run' => $run, '--apply' => true])->assertSuccessful();
        $this->assertSame(1, $this->state($run)['after']['orphan']);
        $this->assertSame(1, $this->state($run)['counts']['invalid_id']);
        $this->assertSame($invalid, $product->fresh()->product_category_id);
    }

    public function test_resume_rejects_changed_mode_or_master_before_writing(): void
    {
        $product = $this->product('Sayur Segar');
        $run = (string) Str::uuid();
        $this->artisan('products:backfill-categories', ['--run' => $run])->assertSuccessful();
        $this->expectException(RuntimeException::class);
        try {
            $this->artisan('products:backfill-categories', ['--run' => $run, '--apply' => true])->run();
        } finally {
            $this->assertNull($product->fresh()->product_category_id);
        }
    }

    public function test_storage_failure_stops_before_product_write(): void
    {
        $product = $this->product('Sayur Segar');
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk = \Mockery::mock());
        $disk->shouldReceive('exists')->once()->andReturn(false);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $this->expectException(RuntimeException::class);
        try {
            $this->artisan('products:backfill-categories', ['--run' => (string) Str::uuid(), '--apply' => true])->run();
        } finally {
            $this->assertNull($product->fresh()->product_category_id);
        }
    }

    public function test_command_reports_concurrent_conflict_and_preserves_new_value(): void
    {
        $product = $this->product('Sayur Segar');
        $this->app->bind(ProductCategoryBackfill::class, fn () => new class extends ProductCategoryBackfill
        {
            public function apply(Product $snapshot, string $targetId, string $targetName): bool
            {
                DB::table('products')->where('id', $snapshot->id)->update(['category' => 'Concurrent change']);

                return parent::apply($snapshot, $targetId, $targetName);
            }
        });
        $run = (string) Str::uuid();
        $this->artisan('products:backfill-categories', ['--run' => $run, '--apply' => true])->assertSuccessful();
        $this->assertSame(1, $this->state($run)['counts']['conflict']);
        $this->assertSame('Concurrent change', $product->fresh()->category);
        $this->assertNull($product->fresh()->product_category_id);
    }

    public function test_invalid_mapping_is_rejected_before_mutating_any_product(): void
    {
        $product = $this->product('Sayur Segar');
        Storage::disk('s3')->put('invalid.json', json_encode(['Sayur Segar' => (string) Str::uuid()]));
        $this->expectException(RuntimeException::class);
        try {
            $this->artisan('products:backfill-categories', ['--run' => (string) Str::uuid(), '--apply' => true, '--mapping' => 'invalid.json'])->run();
        } finally {
            $this->assertNull($product->fresh()->product_category_id);
        }
    }

    public function test_parallel_runner_is_rejected_by_isolation_lock(): void
    {
        $lock = Cache::lock('framework/command-products:backfill-categories', 60);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('products:backfill-categories', ['--run' => (string) Str::uuid(), '--isolated' => 1])->assertExitCode(1);
            $this->assertSame([], Storage::disk('s3')->allFiles());
        } finally {
            $lock->release();
        }
    }
}
