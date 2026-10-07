<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ProductCategoryWriter;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\PromoProductSeeder;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductCategoryWebWriterTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ProductCategory $category;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $seller = User::factory()->create(['role' => User::ROLE_SELLER]);
        $this->actingAs($seller);
        $this->tenant = Tenant::query()->create(['owner_user_id' => $seller->id, 'name' => 'Toko', 'category' => 'Lama']);
        $this->category = ProductCategory::query()->create(['image_path' => 'images/category.svg', 'name' => 'Master baru', 'slug' => 'master-baru']);
        $this->payload = ['tenant_id' => $this->tenant->id, 'name' => 'Produk', 'price' => 1000, 'product_category_id' => $this->category->id];
    }

    public function test_web_create_and_edit_use_master_and_preselect_saved_id(): void
    {
        $this->get('/seller/products/create')->assertOk()->assertSee($this->category->name)->assertSee('name="product_category_id"', false);
        $this->post('/seller/products', $this->payload + ['category' => 'Ignored'])->assertRedirect(route('seller.products.index'));
        $product = Product::query()->firstOrFail();
        $this->assertSame($this->category->id, $product->product_category_id);
        $this->assertSame($this->category->name, $product->category);
        $this->get('/seller/products/'.$product->id.'/edit')->assertOk()->assertSee('value="'.$this->category->id.'" selected', false);
        $new = ProductCategory::query()->create(['image_path' => 'images/category.svg', 'name' => 'Master kedua', 'slug' => 'kedua']);
        $this->put('/seller/products/'.$product->id, array_replace($this->payload, ['product_category_id' => $new->id]))->assertRedirect(route('seller.products.index'));
        $this->assertSame($new->name, $product->fresh()->category);
    }

    public function test_legacy_record_requires_explicit_selection_and_invalid_id_is_rejected(): void
    {
        $product = Product::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Lama', 'price' => 1, 'category' => 'Tidak terpetakan']);
        $this->get('/seller/products/'.$product->id.'/edit')->assertOk()->assertSee('belum dipetakan')->assertDontSee('value="'.$this->category->id.'" selected', false);
        foreach ([null, '', 'bad'] as $id) {
            $this->put('/seller/products/'.$product->id, array_replace($this->payload, ['product_category_id' => $id]))->assertSessionHasErrors('product_category_id');
        }
        $this->assertNull($product->fresh()->product_category_id);
    }

    public function test_web_ownership_is_enforced_on_object_and_destination_tenant(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_SELLER]);
        $tenant = Tenant::query()->create(['owner_user_id' => $other->id, 'name' => 'Lain', 'category' => 'Lain']);
        $product = Product::query()->create(['tenant_id' => $tenant->id, 'name' => 'Lain', 'price' => 1, 'category' => 'Lain']);
        $this->get('/seller/products/'.$product->id.'/edit')->assertNotFound();
        $this->put('/seller/products/'.$product->id, $this->payload)->assertNotFound();
        $this->post('/seller/products', array_replace($this->payload, ['tenant_id' => $tenant->id]))->assertSessionHasErrors('tenant_id');
        $this->assertSame('Lain', $product->fresh()->category);
    }

    public function test_shared_admin_writer_dual_writes_and_rejects_invalid_selection(): void
    {
        $writer = app(ProductCategoryWriter::class);
        $data = $writer->attributes($this->payload + ['category' => 'Wrong']);
        $this->assertSame($this->category->name, $data['category']);
        $this->assertSame($this->category->id, $data['product_category_id']);
        $this->expectException(ValidationException::class);
        $writer->attributes(['category' => $this->category->name]);
    }

    public function test_fixture_seeders_dual_write_and_preserve_ids_on_repeat(): void
    {
        $this->seed([ProductCategorySeeder::class, TenantSeeder::class, PromoProductSeeder::class]);
        $products = Product::query()->with('productCategory')->get();
        $this->assertGreaterThan(0, $products->count());
        foreach ($products as $product) {
            $this->assertNotNull($product->productCategory);
            $this->assertSame($product->productCategory->name, $product->category);
        }
        $ids = $products->pluck('product_category_id', 'id')->all();
        $this->seed([ProductCategorySeeder::class, TenantSeeder::class, PromoProductSeeder::class]);
        $this->assertSame($ids, Product::query()->get()->pluck('product_category_id', 'id')->all());
    }
}
