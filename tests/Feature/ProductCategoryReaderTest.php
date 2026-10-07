<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductCategoryReaderTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private User $buyer;

    private Tenant $tenant;

    private ProductCategory $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->create(['role' => User::ROLE_SELLER]);
        $this->buyer = User::factory()->create(['role' => User::ROLE_BUYER]);
        foreach (['seller' => $this->seller, 'buyer' => $this->buyer] as $token => $user) {
            UserSessionToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        }
        $this->tenant = Tenant::query()->create(['owner_user_id' => $this->seller->id, 'name' => 'Toko', 'category' => 'Tenant legacy']);
        $this->category = ProductCategory::query()->create(['name' => 'Sayur', 'slug' => 'sayur', 'image_path' => 'images/category.svg']);
        $this->product = $this->product();
    }

    private function product(array $overrides = []): Product
    {
        return Product::query()->create(array_replace(['tenant_id' => $this->tenant->id, 'name' => 'Produk', 'category' => 'Stale text', 'product_category_id' => $this->category->id, 'price' => 1000, 'is_active' => true], $overrides));
    }

    public function test_master_rename_is_visible_in_seller_and_buyer_list_detail_with_stable_slug(): void
    {
        $this->category->update(['name' => 'Sayur renamed']);
        foreach (['seller' => ['/api/seller/products', '/api/seller/products/'.$this->product->id], 'buyer' => ['/api/products', '/api/products/'.$this->product->id]] as $token => $urls) {
            foreach ($urls as $index => $url) {
                $prefix = $index === 0 ? 'data.0' : 'data';
                $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson($url);
                $response->assertOk()->assertJsonPath($prefix.'.category', 'Sayur renamed')
                    ->assertJsonPath($prefix.'.product_category_id', $this->category->id)
                    ->assertJsonPath($prefix.'.product_category', ['id' => $this->category->id, 'name' => 'Sayur renamed', 'slug' => 'sayur']);
                if ($token === 'buyer') {
                    $response->assertJsonPath($prefix.'.category_slug', 'sayur')->assertJsonPath($prefix.'.category_icon_key', 'vegetables');
                }
            }
        }
        $this->assertSame('Stale text', $this->product->fresh()->category);
    }

    public function test_new_master_uses_neutral_metadata_without_tenant_constants(): void
    {
        $this->category->update(['name' => 'Kategori Baru', 'slug' => 'new-custom-slug']);
        $this->withHeader('Authorization', 'Bearer buyer')->getJson('/api/products/'.$this->product->id)
            ->assertOk()->assertJsonPath('data.category_slug', 'new-custom-slug')
            ->assertJsonPath('data.category_icon_key', 'category')
            ->assertJsonPath('data.category_background_color', '#F3F4F6')
            ->assertJsonPath('data.category_icon_color', '#6B7280');
    }

    public function test_visibility_and_seller_ownership_remain_unchanged(): void
    {
        $inactive = $this->product(['is_active' => false]);
        $deleted = $this->product();
        $deleted->delete();
        $this->withHeader('Authorization', 'Bearer buyer')->getJson('/api/products')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/products/'.$inactive->id)->assertNotFound();
        $this->getJson('/api/products/'.$deleted->id)->assertNotFound();
        $this->withHeader('Authorization', 'Bearer seller')->getJson('/api/seller/products')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/seller/products/'.$inactive->id)->assertOk();
        $this->getJson('/api/seller/products/'.$deleted->id)->assertNotFound();
        $other = User::factory()->create(['role' => User::ROLE_SELLER]);
        $this->tenant->update(['owner_user_id' => $other->id]);
        $this->getJson('/api/seller/products/'.$this->product->id)->assertNotFound();
    }

    public function test_legacy_null_id_retains_text_only_until_backfill(): void
    {
        $legacy = $this->product(['product_category_id' => null, 'category' => 'Sayur']);
        $this->withHeader('Authorization', 'Bearer buyer')->getJson('/api/products/'.$legacy->id)
            ->assertOk()->assertJsonPath('data.product_category_id', null)->assertJsonPath('data.product_category', null)
            ->assertJsonPath('data.category', 'Sayur')->assertJsonPath('data.category_slug', 'sayur');
        $legacy->update(['product_category_id' => $this->category->id]);
        $this->category->update(['name' => 'Master name']);
        $this->getJson('/api/products/'.$legacy->id)->assertOk()->assertJsonPath('data.category', 'Master name');
    }

    public function test_list_and_nested_cart_eager_load_master_in_one_query(): void
    {
        $products = collect([$this->product]);
        for ($index = 0; $index < 5; $index++) {
            $products->push($this->product());
        }
        foreach ($products as $product) {
            CartItem::query()->create(['user_id' => $this->buyer->id, 'product_id' => $product->id, 'quantity' => 1]);
        }
        foreach (['/api/seller/products' => 'seller', '/api/products' => 'buyer', '/api/cart' => 'buyer'] as $url => $token) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson($url)->assertOk();
            $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"product_categories"'));
            DB::disableQueryLog();
            $this->assertCount(1, $queries, $url);
            if ($url === '/api/cart') {
                $response->assertJsonPath('data.items.0.product.product_category.id', $this->category->id)->assertJsonPath('data.subtotal', 6000);
            }
        }
    }

    public function test_web_list_displays_renamed_master(): void
    {
        $this->category->update(['name' => 'Renamed for web']);
        $this->actingAs($this->seller)->get('/seller/products')->assertOk()->assertSee('Renamed for web')->assertDontSee('Stale text');
    }
}
