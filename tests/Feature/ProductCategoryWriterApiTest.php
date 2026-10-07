<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCategoryWriterApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ProductCategory $category;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $seller = User::factory()->create(['role' => User::ROLE_SELLER]);
        UserSessionToken::query()->create(['user_id' => $seller->id, 'token' => hash('sha256', 'category-token'), 'expires_at' => now()->addDay()]);
        $this->withHeader('Authorization', 'Bearer category-token');
        $this->tenant = Tenant::query()->create(['owner_user_id' => $seller->id, 'name' => 'Toko', 'category' => 'Kategori tenant']);
        $this->category = ProductCategory::query()->create(['image_path' => 'images/category.svg', 'name' => 'Kategori Baru', 'slug' => 'kategori-baru', 'image_path' => 'private']);
        $unit = ProductUnit::query()->create(['name' => 'pcs', 'slug' => 'pcs', 'is_active' => true]);
        $this->payload = ['tenant_id' => $this->tenant->id, 'name' => 'Produk', 'price' => 1000, 'stock' => 3, 'product_unit_id' => $unit->id, 'image_url' => 'https://example.com/image.png'];
        Storage::fake(Product::imageDisk());
    }

    private function product(): Product
    {
        return Product::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Lama', 'category' => 'Belum dipetakan', 'price' => 500, 'stock' => 1]);
    }

    public function test_create_dual_writes_id_and_master_name_with_allowlisted_response(): void
    {
        $response = $this->postJson('/api/seller/products', $this->payload + ['product_category_id' => $this->category->id, 'category' => ['ignored']]);
        $response->assertCreated()->assertJsonPath('data.product_category_id', $this->category->id)
            ->assertJsonPath('data.category', $this->category->name)
            ->assertJsonPath('data.product_category', ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug]);
        $this->assertDatabaseHas('products', ['id' => $response->json('data.id'), 'product_category_id' => $this->category->id, 'category' => $this->category->name]);
    }

    public function test_create_requires_valid_id_even_with_valid_text(): void
    {
        foreach ([[], ['product_category_id' => null], ['product_category_id' => ''], ['product_category_id' => 'bad'], ['product_category_id' => (string) Str::uuid()]] as $id) {
            $this->postJson('/api/seller/products', $this->payload + $id + ['category' => $this->category->name])->assertUnprocessable()->assertJsonValidationErrors('product_category_id');
        }
        $this->assertDatabaseCount('products', 0);
    }

    public function test_put_and_multipart_post_prioritize_id_and_dual_write(): void
    {
        $product = $this->product();
        $payload = $this->payload + ['product_category_id' => $this->category->id, 'category' => ['ignored']];
        $this->putJson('/api/seller/products/'.$product->id, $payload)->assertOk()->assertJsonPath('data.product_category.slug', $this->category->slug);
        $this->post('/api/seller/products/'.$product->id, $payload + ['image' => UploadedFile::fake()->image('image.png')])->assertOk()->assertJsonPath('data.category', $this->category->name);
        $product->refresh();
        $this->assertSame($this->category->id, $product->product_category_id);
        $this->assertSame($this->category->name, $product->category);
        Storage::disk(Product::imageDisk())->assertExists($product->image_path);
    }

    public function test_explicit_invalid_id_never_falls_back(): void
    {
        $product = $this->product();
        Log::spy();
        foreach ([null, '', 'bad', (string) Str::uuid()] as $id) {
            foreach (['putJson', 'postJson'] as $method) {
                $this->$method('/api/seller/products/'.$product->id, $this->payload + ['product_category_id' => $id, 'category' => $this->category->name])->assertUnprocessable()->assertJsonValidationErrors('product_category_id');
            }
        }
        Log::shouldNotHaveReceived('info');
        $this->assertSame('Belum dipetakan', $product->fresh()->category);
    }

    public function test_legacy_edit_accepts_unique_exact_and_normalized_names_and_logs_no_values(): void
    {
        $product = $this->product();
        Log::spy();
        foreach ([$this->category->name, "  KATEGORI \t BARU  "] as $name) {
            $this->putJson('/api/seller/products/'.$product->id, $this->payload + ['category' => $name])->assertOk()->assertJsonPath('data.product_category_id', $this->category->id);
        }
        Log::shouldHaveReceived('info')->with('product_category_legacy_fallback', \Mockery::on(fn ($context) => $context['outcome'] === 'accepted' && array_keys($context) === ['outcome', 'route', 'method', 'environment', 'request_id'] && Str::isUuid($context['request_id'])))->twice();
    }

    public function test_ambiguous_and_unknown_legacy_names_are_rejected_and_observable(): void
    {
        $product = $this->product();
        ProductCategory::query()->create(['image_path' => 'images/category.svg', 'name' => 'KATEGORI  BARU', 'slug' => 'duplicate-normalized']);
        Log::spy();
        foreach (['kategori baru', 'Tidak ada', '', null, ['bad']] as $name) {
            $this->putJson('/api/seller/products/'.$product->id, $this->payload + ['category' => $name])->assertUnprocessable()->assertJsonValidationErrors('category');
        }
        Log::shouldHaveReceived('info')->with('product_category_legacy_fallback', \Mockery::on(fn ($context) => $context['outcome'] === 'rejected'))->times(5);
        // An exact unique match still wins over normalized ambiguity.
        $this->putJson('/api/seller/products/'.$product->id, $this->payload + ['category' => $this->category->name])->assertOk();
        ProductCategory::query()->create(['image_path' => 'images/category.svg', 'name' => $this->category->name, 'slug' => 'duplicate-exact']);
        $this->putJson('/api/seller/products/'.$product->id, $this->payload + ['category' => $this->category->name])->assertUnprocessable();
    }

    public function test_auth_ownership_and_other_validation_remain_enforced(): void
    {
        $product = $this->product();
        $other = User::factory()->create(['role' => User::ROLE_SELLER]);
        $otherTenant = Tenant::query()->create(['owner_user_id' => $other->id, 'name' => 'Lain', 'category' => 'Lain']);
        $otherProduct = Product::query()->create(['tenant_id' => $otherTenant->id, 'name' => 'Lain', 'category' => 'Lain', 'price' => 1]);
        $valid = $this->payload + ['product_category_id' => $this->category->id];
        $this->putJson('/api/seller/products/'.$otherProduct->id, $valid)->assertNotFound();
        $this->putJson('/api/seller/products/'.$product->id, array_replace($valid, ['tenant_id' => $otherTenant->id]))->assertUnprocessable()->assertJsonValidationErrors('tenant_id');
        $this->putJson('/api/seller/products/'.$product->id, array_replace($valid, ['price' => -1]))->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->withHeader('Authorization', 'Bearer invalid')->putJson('/api/seller/products/'.$product->id, $valid)->assertUnauthorized();
        $this->assertSame('Belum dipetakan', $product->fresh()->category);
    }
}
