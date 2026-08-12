<?php

namespace Tests\Feature;

use App\Models\CancellationReasonCategory;
use App\Models\HousingArea;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Models\UserSessionToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_and_list_own_tenant(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

        [$seller, $token] = $this->createAuthenticatedUser('seller@example.com', '+6281200000001', 'seller-token', User::ROLE_SELLER);
        [$agent] = $this->createAuthenticatedUser('agent-seller@example.com', '+6281200000099', 'agent-token', User::ROLE_AGENT);
        $agent->forceFill(['agent_code' => 'KA-20265'])->save();
        $category = ProductCategory::query()->create([
            'name' => Tenant::CATEGORY_GROCERIES,
            'slug' => 'sembako',
            'image_path' => 'images/ic_groceries_category.svg',
        ]);
        $housingArea = HousingArea::query()->create([
            'name' => 'Komp Setra Dago',
            'code' => 'AREA-001',
            'city' => 'Kota Bandung',
            'district' => 'Antapani',
            'subdistrict' => 'Antapani Wetan',
            'village_code' => '3273141003',
        ]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/tenants', [
                'owner_name' => 'Asep Pemilik',
                'owner_phone' => '081234567890',
                'owner_email' => 'asep@example.com',
                'agent_code' => 'KA-20265',
                'name' => 'Tenant Seller',
                'category_id' => $category->id,
                'location' => 'Jl Asri Raya No 45',
                'housing_area_ids' => [$housingArea->id],
                'profile_picture_url' => 'https://example.com/seller-tenant.png',
                'latitude' => -6.2,
                'longitude' => 106.8,
                'open_time' => '07:00',
                'close_time' => '21:00',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.owner_user_id', $seller->id)
            ->assertJsonPath('data.agent_user_id', $agent->id)
            ->assertJsonPath('data.agent_code', 'KA-20265')
            ->assertJsonPath('data.owner.name', 'Asep Pemilik')
            ->assertJsonPath('data.owner.phone', '081234567890')
            ->assertJsonPath('data.owner.email', 'asep@example.com')
            ->assertJsonPath('data.name', 'Tenant Seller')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.category', Tenant::CATEGORY_GROCERIES)
            ->assertJsonPath('data.category_master.slug', 'sembako')
            ->assertJsonPath('data.location', 'Jl Asri Raya No 45')
            ->assertJsonPath('data.housing_areas.0.id', $housingArea->id);

        $pivotId = DB::table('housing_area_tenant')
            ->where('tenant_id', $createResponse->json('data.id'))
            ->where('housing_area_id', $housingArea->id)
            ->value('id');

        $this->assertNotNull($pivotId);

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.owner_user_id', $seller->id)
            ->assertJsonPath('data.0.agent_code', 'KA-20265')
            ->assertJsonPath('data.0.name', 'Tenant Seller')
            ->assertJsonPath('data.0.category_id', $category->id)
            ->assertJsonPath('data.0.location', 'Jl Asri Raya No 45')
            ->assertJsonPath('data.0.housing_areas.0.name', 'Komp Setra Dago')
            ->assertJsonPath('data.0.is_open', true)
            ->assertJsonPath('data.0.store_status', 'Buka')
            ->assertJsonPath('data.0.operating_hours_label', 'Buka 07:00 sd 21:00');

        Carbon::setTestNow();
    }

    public function test_seller_tenant_area_is_limited_to_three_housing_areas(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-area@example.com', '+6281200000040', 'seller-area-token', User::ROLE_SELLER);
        [$agent] = $this->createAuthenticatedUser('agent-area@example.com', '+6281200000041', 'agent-area-token', User::ROLE_AGENT);
        $agent->forceFill(['agent_code' => 'KA-30001'])->save();
        $category = ProductCategory::query()->create([
            'name' => Tenant::CATEGORY_GROCERIES,
            'slug' => 'sembako',
            'image_path' => 'images/ic_groceries_category.svg',
        ]);

        $housingAreaIds = collect(range(1, 4))
            ->map(fn (int $number) => HousingArea::query()->create([
                'name' => 'Komp Area '.$number,
                'code' => 'AREA-'.$number,
                'village_code' => '3273141003',
            ])->id)
            ->all();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/tenants', [
                'owner_name' => 'Pemilik Area',
                'agent_code' => 'KA-30001',
                'name' => 'Tenant Area',
                'category_id' => $category->id,
                'location' => 'Jl Area No 1',
                'housing_area_ids' => $housingAreaIds,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['housing_area_ids']);
    }

    public function test_seller_can_create_tenant_without_agent_code(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-no-agent@example.com', '+6281200000042', 'seller-no-agent-token', User::ROLE_SELLER);
        $category = ProductCategory::query()->create([
            'name' => Tenant::CATEGORY_GROCERIES,
            'slug' => 'sembako-no-agent',
            'image_path' => 'images/ic_groceries_category.svg',
        ]);
        $housingArea = HousingArea::query()->create([
            'name' => 'Komp Tanpa Agent',
            'code' => 'AREA-NO-AGENT',
            'village_code' => '3273141003',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/tenants', [
                'owner_name' => 'Pemilik Tanpa Agent',
                'name' => 'Tenant Tanpa Agent',
                'category_id' => $category->id,
                'location' => 'Jl Mandiri No 1',
                'housing_area_ids' => [$housingArea->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.owner_user_id', $seller->id)
            ->assertJsonPath('data.agent_user_id', null)
            ->assertJsonPath('data.agent_code', null)
            ->assertJsonPath('data.name', 'Tenant Tanpa Agent');

        $this->assertDatabaseHas('tenants', [
            'owner_user_id' => $seller->id,
            'agent_user_id' => null,
            'name' => 'Tenant Tanpa Agent',
        ]);
    }

    public function test_seller_can_create_and_list_own_product(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller2@example.com', '+6281200000002', 'seller-token-2', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Seller Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);
        $category = $this->createProductCategory(Tenant::CATEGORY_VEGETABLES);

        $unit = $this->createProductUnit('ikat');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'product_category_id' => $category->id,
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'original_price' => 9000,
                'stock' => 100,
                'product_unit_id' => $unit->id,
                'minimum_stock' => 5,
                'is_active' => true,
                'weight_label' => '250gr',
                'description' => 'Sayur segar.',
                'delivery_estimate' => 'Hari ini',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.name', 'Bayam')
            ->assertJsonPath('data.category', Tenant::CATEGORY_VEGETABLES)
            ->assertJsonPath('data.stock', 100)
            ->assertJsonPath('data.unit', 'ikat')
            ->assertJsonPath('data.product_unit.name', 'ikat')
            ->assertJsonPath('data.minimum_stock', 5)
            ->assertJsonPath('data.is_active', true);

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.tenant_id', $tenant->id)
            ->assertJsonPath('data.0.name', 'Bayam')
            ->assertJsonPath('data.0.stock', 100)
            ->assertJsonPath('data.0.unit', 'ikat')
            ->assertJsonPath('data.0.product_unit.name', 'ikat');
    }

    public function test_seller_can_search_own_products_by_name(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-product-search@example.com', '+6281200000144', 'seller-product-search-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('other-product-search@example.com', '+6281200000145', 'other-product-search-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Product Search',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $otherTenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Tenant Product Search',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam Hijau',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 100,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Wortel Segar',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 9000,
            'stock' => 50,
        ]);

        Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Bayam Seller Lain',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 8000,
            'stock' => 20,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products?name=bayam')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bayam Hijau');
    }

    public function test_seller_product_list_includes_created_at_and_sold_quantity(): void
    {
        Carbon::setTestNow('2026-04-15 09:30:00');

        [$seller, $token] = $this->createAuthenticatedUser('seller-product-sold@example.com', '+6281200000244', 'seller-product-sold-token', User::ROLE_SELLER);
        [$buyer] = $this->createAuthenticatedUser('buyer-product-sold@example.com', '+6281200000245', 'buyer-product-sold-token', User::ROLE_BUYER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Product Sold',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Kangkung',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 6000,
            'stock' => 40,
        ]);

        $completedTransaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => 'SOLD001',
            'status' => Transaction::STATUS_COMPLETED,
            'subtotal_amount' => 18000,
            'delivery_fee' => 0,
            'total_amount' => 18000,
            'transaction_at' => now(),
        ]);

        $canceledTransaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => 'SOLD002',
            'status' => Transaction::STATUS_CANCELED,
            'subtotal_amount' => 12000,
            'delivery_fee' => 0,
            'total_amount' => 12000,
            'transaction_at' => now(),
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $completedTransaction->id,
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 6000,
            'line_total' => 18000,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $canceledTransaction->id,
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 6000,
            'line_total' => 12000,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.created_at', '2026-04-15T09:30:00+00:00')
            ->assertJsonPath('data.0.sold', 3);
    }

    public function test_seller_cannot_create_product_with_unregistered_unit(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-invalid-unit@example.com', '+6281200000045', 'seller-invalid-unit-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Invalid Unit',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);
        $category = $this->createProductCategory(Tenant::CATEGORY_VEGETABLES);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'product_category_id' => $category->id,
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'stock' => 10,
                'product_unit_id' => (string) str()->uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_unit_id']);
    }

    public function test_seller_cannot_create_product_with_unregistered_category(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-invalid-category@example.com', '+6281200000047', 'seller-invalid-category-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Invalid Category',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $unit = $this->createProductUnit('ikat');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'product_category_id' => (string) str()->uuid(),
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'stock' => 10,
                'product_unit_id' => $unit->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_category_id']);
    }

    public function test_seller_cannot_update_product_with_unregistered_unit(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-invalid-update-unit@example.com', '+6281200000046', 'seller-invalid-update-unit-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Invalid Update Unit',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/bayam.png',
            'price' => 7000,
            'stock' => 10,
            'unit' => 'ikat',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/seller/products/'.$product->id, [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'category' => Tenant::CATEGORY_VEGETABLES,
                'image_url' => 'https://example.com/bayam.png',
                'price' => 7000,
                'stock' => 10,
                'product_unit_id' => (string) str()->uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_unit_id']);
    }

    public function test_seller_cannot_create_product_for_other_sellers_tenant(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller3@example.com', '+6281200000003', 'seller-token-3', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller4@example.com', '+6281200000004', 'seller-token-4', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Tenant',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);
        $category = $this->createProductCategory(Tenant::CATEGORY_VEGETABLES);

        $unit = $this->createProductUnit('ikat');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Bayam',
                'product_category_id' => $category->id,
                'price' => 7000,
                'stock' => 10,
                'product_unit_id' => $unit->id,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_seller_can_upload_product_image_when_creating_product(): void
    {
        Storage::fake(Product::imageDisk());

        [$seller, $token] = $this->createAuthenticatedUser('seller-upload@example.com', '+6281200000043', 'seller-upload-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Upload Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);
        $category = $this->createProductCategory(Tenant::CATEGORY_VEGETABLES);

        $unit = $this->createProductUnit('ikat');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Pakcoy Lokal',
                'product_category_id' => $category->id,
                'image' => UploadedFile::fake()->image('pakcoy.jpg'),
                'price' => 9999,
                'original_price' => 12000,
                'stock' => 100,
                'product_unit_id' => $unit->id,
                'minimum_stock' => 5,
                'is_active' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pakcoy Lokal')
            ->assertJsonPath('data.stock', 100)
            ->assertJsonPath('data.unit', 'ikat');

        $product = Product::query()->where('name', 'Pakcoy Lokal')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk(Product::imageDisk())->assertExists($product->image_path);
    }

    public function test_seller_can_upload_product_image_separately_and_create_product_with_image_path(): void
    {
        Storage::fake(Product::imageDisk());

        [$seller, $token] = $this->createAuthenticatedUser('seller-upload-path@example.com', '+6281200000044', 'seller-upload-path-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Upload Path Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);
        $category = $this->createProductCategory(Tenant::CATEGORY_VEGETABLES);

        $uploadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/seller/product-images', [
                'image' => UploadedFile::fake()->image('wortel.png'),
            ]);

        $uploadResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Gambar produk berhasil diupload.');

        $imagePath = $uploadResponse->json('data.image_path');

        Storage::disk(Product::imageDisk())->assertExists($imagePath);

        $unit = $this->createProductUnit('kilogram');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/seller/products', [
                'tenant_id' => $tenant->id,
                'name' => 'Wortel Lokal',
                'product_category_id' => $category->id,
                'image_path' => $imagePath,
                'price' => 10000,
                'stock' => 50,
                'product_unit_id' => $unit->id,
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Wortel Lokal')
            ->assertJsonPath('data.unit', 'kilogram');

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Wortel Lokal',
            'image_path' => $imagePath,
            'stock' => 50,
            'unit' => 'kilogram',
            'product_unit_id' => $unit->id,
        ]);
    }

    public function test_seller_can_get_product_summary_counts(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-product-summary@example.com', '+6281200000052', 'seller-product-summary-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('other-product-summary@example.com', '+6281200000053', 'other-product-summary-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Product Summary',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $otherTenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Tenant Product Summary',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk Aktif Aman',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 20,
            'unit' => 'ikat',
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk Aktif Stok Sedikit',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 5,
            'unit' => 'ikat',
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk Nonaktif Stok Sedikit',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 0,
            'unit' => 'ikat',
            'minimum_stock' => 1,
            'is_active' => false,
        ]);

        Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Produk Seller Lain',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 0,
            'unit' => 'ikat',
            'minimum_stock' => 1,
            'is_active' => false,
        ]);

        $deletedProduct = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk Terhapus',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'price' => 7000,
            'stock' => 0,
            'unit' => 'ikat',
            'minimum_stock' => 1,
            'is_active' => false,
        ]);
        $deletedProduct->delete();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products/summary')
            ->assertOk()
            ->assertJsonPath('message', 'Ringkasan produk seller berhasil diambil.')
            ->assertJsonPath('data.total_products', 3)
            ->assertJsonPath('data.active_products', 2)
            ->assertJsonPath('data.inactive_products', 1)
            ->assertJsonPath('data.low_stock_products', 2);
    }

    public function test_seller_can_list_and_view_own_orders(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-order@example.com', '+6281200000007', 'seller-order-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('other-seller-order@example.com', '+6281200000008', 'other-seller-order-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($seller, 'ORDER001', Transaction::STATUS_PROCESSING);
        $buyer = $order->user;
        $buyer->forceFill([
            'address' => 'Alamat profil terbaru',
            'landmark' => 'Landmark terbaru',
            'latitude' => -7.1,
            'longitude' => 108.1,
        ])->save();
        $order->forceFill([
            'buyer_address' => 'Jl. Mawar No. 10',
            'buyer_landmark' => null,
            'buyer_latitude' => -6.914744,
            'buyer_longitude' => 107.60981,
            'buyer_address_snapshot_at' => now(),
            'service_fee' => 1000,
        ])->save();
        $otherOrder = $this->createOrderForSeller($otherSeller, 'ORDER002', Transaction::STATUS_PROCESSING);

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.order_number', 'ORDER001')
            ->assertJsonPath('data.0.status_code', Transaction::STATUS_CODE_PROCESSING)
            ->assertJsonPath('data.0.buyer.address', 'Jl. Mawar No. 10')
            ->assertJsonPath('data.0.buyer.landmark', null)
            ->assertJsonPath('data.0.buyer.latitude', -6.914744)
            ->assertJsonPath('data.0.buyer.longitude', 107.60981)
            ->assertJsonPath('data.0.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.0.delivery_method_code', 'store_courier')
            ->assertJsonPath('data.0.payment_method', Transaction::PAYMENT_METHOD_QRIS)
            ->assertJsonPath('data.0.payment_method_code', 'qr_payment')
            ->assertJsonPath('data.0.payment_method_option_name', null)
            ->assertJsonPath('data.0.payment_method_option_code', null)
            ->assertJsonMissing(['email' => $otherOrder->user->email])
            ->assertJsonPath('meta.total', 1);

        $detailResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id);

        $detailResponse
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', 'ORDER001')
            ->assertJsonPath('data.buyer.address', 'Jl. Mawar No. 10')
            ->assertJsonPath('data.buyer.landmark', null)
            ->assertJsonPath('data.buyer.latitude', -6.914744)
            ->assertJsonPath('data.buyer.longitude', 107.60981)
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.delivery_method_code', 'store_courier')
            ->assertJsonPath('data.payment_method', Transaction::PAYMENT_METHOD_QRIS)
            ->assertJsonPath('data.payment_method_code', 'qr_payment')
            ->assertJsonPath('data.payment_method_option_name', null)
            ->assertJsonPath('data.payment_method_option_code', null)
            ->assertJsonPath('data.service_fee', 1000)
            ->assertJsonPath('data.service_fee_label', 'Rp. 1.000')
            ->assertJsonPath('data.items.0.product_name', 'Produk ORDER001')
            ->assertJsonPath('data.status_timelines.0.status_code', Transaction::STATUS_CODE_PROCESSING);
    }

    public function test_seller_order_list_and_detail_fall_back_to_current_buyer_address_for_legacy_order(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-legacy-order@example.com', '+6281200000051', 'seller-legacy-order-token', User::ROLE_SELLER);
        $order = $this->createOrderForSeller($seller, 'ORDERLEGACY', Transaction::STATUS_PROCESSING);
        $order->user->forceFill([
            'address' => 'Jl. Alamat Buyer Saat Ini',
            'landmark' => null,
            'latitude' => null,
            'longitude' => null,
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders')
            ->assertOk()
            ->assertJsonPath('data.0.buyer.address', 'Jl. Alamat Buyer Saat Ini')
            ->assertJsonPath('data.0.buyer.landmark', null)
            ->assertJsonPath('data.0.buyer.latitude', null)
            ->assertJsonPath('data.0.buyer.longitude', null);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.buyer.address', 'Jl. Alamat Buyer Saat Ini')
            ->assertJsonPath('data.buyer.landmark', null)
            ->assertJsonPath('data.buyer.latitude', null)
            ->assertJsonPath('data.buyer.longitude', null);
    }

    public function test_seller_order_does_not_mix_null_snapshot_fields_with_current_buyer_profile(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-null-snapshot@example.com', '+6281200000052', 'seller-null-snapshot-token', User::ROLE_SELLER);
        $order = $this->createOrderForSeller($seller, 'ORDERNULLSNAP', Transaction::STATUS_PROCESSING);
        $order->user->forceFill([
            'address' => 'Alamat profil yang tidak boleh dipakai',
            'landmark' => 'Landmark profil yang tidak boleh dipakai',
            'latitude' => -7.2,
            'longitude' => 108.2,
        ])->save();
        $order->forceFill([
            'buyer_address' => null,
            'buyer_landmark' => null,
            'buyer_latitude' => null,
            'buyer_longitude' => null,
            'buyer_address_snapshot_at' => now(),
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.buyer.address', null)
            ->assertJsonPath('data.buyer.landmark', null)
            ->assertJsonPath('data.buyer.latitude', null)
            ->assertJsonPath('data.buyer.longitude', null);
    }

    public function test_seller_order_list_and_detail_include_bank_transfer_option_and_pickup_method(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-bank-order@example.com', '+6281200000053', 'seller-bank-order-token', User::ROLE_SELLER);
        $order = $this->createOrderForSeller($seller, 'ORDERBANK', Transaction::STATUS_PROCESSING, 'pickup');
        $order->forceFill([
            'payment_method' => Transaction::PAYMENT_METHOD_BANK_TRANSFER,
            'payment_method_code' => 'bank_transfer',
            'payment_method_option_name' => 'BCA',
            'payment_method_option_code' => 'bca',
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders')
            ->assertOk()
            ->assertJsonPath('data.0.delivery_method', 'Ambil ke Toko')
            ->assertJsonPath('data.0.delivery_method_code', 'pickup')
            ->assertJsonPath('data.0.payment_method', Transaction::PAYMENT_METHOD_BANK_TRANSFER)
            ->assertJsonPath('data.0.payment_method_code', 'bank_transfer')
            ->assertJsonPath('data.0.payment_method_option_name', 'BCA')
            ->assertJsonPath('data.0.payment_method_option_code', 'bca');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.delivery_method', 'Ambil ke Toko')
            ->assertJsonPath('data.delivery_method_code', 'pickup')
            ->assertJsonPath('data.payment_method', Transaction::PAYMENT_METHOD_BANK_TRANSFER)
            ->assertJsonPath('data.payment_method_code', 'bank_transfer')
            ->assertJsonPath('data.payment_method_option_name', 'BCA')
            ->assertJsonPath('data.payment_method_option_code', 'bca');
    }

    public function test_seller_can_access_split_dashboard_apis_for_own_store(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00', 'Asia/Jakarta'));

        [$seller, $token] = $this->createAuthenticatedUser('seller-dashboard@example.com', '+6281200000041', 'seller-dashboard-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('other-seller-dashboard@example.com', '+6281200000042', 'other-seller-dashboard-token', User::ROLE_SELLER);

        $this->createOrderForSeller($seller, 'DASH001', Transaction::STATUS_COMPLETED);
        $newToday = $this->createOrderForSeller($seller, 'DASH002', Transaction::STATUS_ACCEPTED_BY_STORE);
        $completedYesterday = $this->createOrderForSeller($seller, 'DASH003', Transaction::STATUS_COMPLETED);
        $completedYesterday->forceFill(['transaction_at' => now()->subDay()])->save();
        $this->createOrderForSeller($otherSeller, 'DASH004', Transaction::STATUS_COMPLETED);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/profile')
            ->assertOk()
            ->assertJsonPath('data.store.is_verified', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/revenue-today')
            ->assertOk()
            ->assertJsonPath('data.today_revenue', 24000)
            ->assertJsonPath('data.today_revenue_label', 'Rp. 24.000');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/revenue-change')
            ->assertOk()
            ->assertJsonPath('data.today_revenue', 24000)
            ->assertJsonPath('data.yesterday_revenue', 24000)
            ->assertJsonPath('data.change_percentage', 0);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/transactions-today')
            ->assertOk()
            ->assertJsonPath('data.today_transaction_count', 2)
            ->assertJsonPath('data.change_percentage', 100);

        $newEarlyJakartaToday = $this->createOrderForSeller($seller, 'DASH005', Transaction::STATUS_ACCEPTED_BY_STORE);
        $newEarlyJakartaToday->forceFill([
            'transaction_at' => Carbon::parse('2026-04-02 01:00:00', 'Asia/Jakarta')->setTimezone('UTC'),
        ])->save();
        $newYesterday = $this->createOrderForSeller($seller, 'DASH006', Transaction::STATUS_ACCEPTED_BY_STORE);
        $newYesterday->forceFill([
            'transaction_at' => Carbon::parse('2026-04-01 23:59:59', 'Asia/Jakarta')->setTimezone('UTC'),
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/orders-today/counts')
            ->assertOk()
            ->assertJsonPath('data.new.count', 2)
            ->assertJsonPath('data.ready_for_pickup.count', 0)
            ->assertJsonPath('data.completed.count', 1)
            ->assertJsonPath('meta.period', 'today')
            ->assertJsonPath('meta.date', '2026-04-02')
            ->assertJsonPath('meta.date_label', '02 April 2026')
            ->assertJsonPath('meta.display_label', 'Hari ini - 02 April 2026')
            ->assertJsonPath('meta.timezone', 'Asia/Jakarta');

        $dashboardResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.today_transaction_count', 3)
            ->assertJsonPath('data.orders_today.status_counts.new.count', 2)
            ->assertJsonPath('data.orders_today.status_counts.completed.count', 1)
            ->assertJsonPath('data.orders_today.preview.0.id', $newToday->id)
            ->assertJsonPath('data.orders_today.preview.1.id', $newEarlyJakartaToday->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/orders/new-preview')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newToday->id)
            ->assertJsonPath('data.0.order_number', 'DASH002')
            ->assertJsonPath('data.0.can_process', true)
            ->assertJsonPath('data.1.id', $newEarlyJakartaToday->id)
            ->assertJsonPath('data', $dashboardResponse->json('data.orders_today.preview'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/top-products-today')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Produk DASH001')
            ->assertJsonPath('data.0.sold_quantity', 2);
    }

    public function test_seller_can_update_order_status_until_completed(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-status@example.com', '+6281200000009', 'seller-status-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($seller, 'ORDER003', Transaction::STATUS_ACCEPTED_BY_STORE);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_PROCESSING,
                'description' => 'Pesanan sedang disiapkan.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_ON_THE_WAY,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_ON_THE_WAY);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED);

        $order->refresh();

        $this->assertSame(Transaction::STATUS_COMPLETED, $order->status);
        $this->assertSame(4, $order->statusHistories()->count());
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $order->id,
            'status' => Transaction::STATUS_PROCESSING,
            'description' => 'Pesanan sedang disiapkan.',
        ]);
    }

    public function test_seller_can_mark_pickup_order_as_ready_for_pickup_until_completed(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-pickup-status@example.com', '+6281200000048', 'seller-pickup-status-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($seller, 'ORDER007', Transaction::STATUS_ACCEPTED_BY_STORE, 'pickup');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_PROCESSING,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_READY_FOR_PICKUP,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_READY_FOR_PICKUP)
            ->assertJsonPath('data.status_label', 'Siap Diambil');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_COMPLETED);

        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $order->id,
            'status' => Transaction::STATUS_READY_FOR_PICKUP,
            'description' => 'Pesanan siap diambil di toko',
        ]);
    }

    public function test_seller_status_update_rejects_invalid_delivery_flow_transition(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-invalid-flow@example.com', '+6281200000049', 'seller-invalid-flow-token', User::ROLE_SELLER);

        $courierOrder = $this->createOrderForSeller($seller, 'ORDER008', Transaction::STATUS_PROCESSING, 'store_courier');
        $pickupOrder = $this->createOrderForSeller($seller, 'ORDER009', Transaction::STATUS_PROCESSING, 'pickup');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$courierOrder->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_READY_FOR_PICKUP,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_code']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$pickupOrder->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_ON_THE_WAY,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_code']);
    }

    public function test_seller_can_cancel_order_with_cancellation_reason_category(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-cancel@example.com', '+6281200000012', 'seller-cancel-token', User::ROLE_SELLER);
        $category = CancellationReasonCategory::query()
            ->where('name', 'Salah Pesan / Salah Produk')
            ->firstOrFail();
        $order = $this->createOrderForSeller($seller, 'ORDER005', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Transaction::STATUS_CODE_CANCELED)
            ->assertJsonPath('data.cancellation_reason.category_id', $category->id)
            ->assertJsonPath('data.cancellation_reason.category_name', 'Salah Pesan / Salah Produk');

        $this->assertDatabaseHas('transactions', [
            'id' => $order->id,
            'status' => Transaction::STATUS_CANCELED,
            'cancellation_reason_category_id' => $category->id,
            'cancellation_reason_text' => null,
        ]);
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $order->id,
            'status' => Transaction::STATUS_CANCELED,
            'description' => 'Pesanan dibatalkan. Alasan: Salah Pesan / Salah Produk',
        ]);
    }

    public function test_seller_cancel_order_requires_free_text_for_other_reason_category(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-cancel-other@example.com', '+6281200000013', 'seller-cancel-other-token', User::ROLE_SELLER);
        $otherReason = CancellationReasonCategory::query()
            ->where('name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->firstOrFail();
        $order = $this->createOrderForSeller($seller, 'ORDER006', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $otherReason->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cancellation_reason_text']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_CANCELED,
                'cancellation_reason_category_id' => $otherReason->id,
                'cancellation_reason_text' => 'Buyer meminta pembatalan melalui chat.',
            ])
            ->assertOk()
            ->assertJsonPath('data.cancellation_reason.category_name', CancellationReasonCategory::OTHER_REASON_NAME)
            ->assertJsonPath('data.cancellation_reason.reason_text', 'Buyer meminta pembatalan melalui chat.');
    }

    public function test_seller_cannot_manage_other_sellers_order(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-no-order@example.com', '+6281200000010', 'seller-no-order-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller-owner-order@example.com', '+6281200000011', 'seller-owner-order-token', User::ROLE_SELLER);

        $order = $this->createOrderForSeller($otherSeller, 'ORDER004', Transaction::STATUS_PROCESSING);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Order tidak ditemukan.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/orders/'.$order->id.'/status', [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
            ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Order tidak ditemukan.');
    }

    public function test_seller_can_update_own_product_status_only(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-product-status@example.com', '+6281200000047', 'seller-product-status-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Product Status',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam Status',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/bayam-status.png',
            'price' => 7000,
            'stock' => 10,
            'unit' => 'ikat',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/products/'.$product->id.'/status', [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Status produk seller berhasil diperbarui.')
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.status_label', 'Nonaktif');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
            'name' => 'Bayam Status',
        ]);
    }

    public function test_seller_product_status_update_requires_is_active(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-product-status-validation@example.com', '+6281200000048', 'seller-product-status-validation-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Product Status Validation',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam Status Validation',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/bayam-status-validation.png',
            'price' => 7000,
            'stock' => 10,
            'unit' => 'ikat',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/products/'.$product->id.'/status', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    public function test_seller_cannot_update_other_sellers_product_status(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller-no-product-status@example.com', '+6281200000049', 'seller-no-product-status-token', User::ROLE_SELLER);
        [$otherSeller] = $this->createAuthenticatedUser('seller-owner-product-status@example.com', '+6281200000050', 'seller-owner-product-status-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $otherSeller->id,
            'name' => 'Other Product Status Tenant',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Other Product Status',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/other-product-status.png',
            'price' => 7000,
            'stock' => 10,
            'unit' => 'ikat',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/seller/products/'.$product->id.'/status', [
                'is_active' => false,
            ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Produk tidak ditemukan.');
    }

    public function test_seller_delete_product_uses_soft_delete(): void
    {
        [$seller, $token] = $this->createAuthenticatedUser('seller-soft-delete@example.com', '+6281200000051', 'seller-soft-delete-token', User::ROLE_SELLER);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant Soft Delete Product',
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bayam Soft Delete',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/bayam-soft-delete.png',
            'price' => 7000,
            'stock' => 10,
            'unit' => 'ikat',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/seller/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('message', 'Produk seller berhasil dihapus.');

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products/'.$product->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Produk tidak ditemukan.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/products')
            ->assertOk()
            ->assertJsonMissing(['id' => $product->id]);
    }

    public function test_buyer_cannot_access_seller_endpoints(): void
    {
        [, $token] = $this->createAuthenticatedUser('buyer@example.com', '+6281200000005', 'buyer-token', User::ROLE_BUYER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/tenants')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role seller.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role seller.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/not-accessible')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role seller.');
    }

    public function test_seller_cannot_access_buyer_checkout_endpoint(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller5@example.com', '+6281200000006', 'seller-token-5', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role buyer.');
    }

    private function createOrderForSeller(User $seller, string $orderNumber, string $status, string $deliveryMethodCode = 'store_courier'): Transaction
    {
        $buyer = User::query()->create([
            'name' => 'Buyer '.$orderNumber,
            'email' => 'buyer-'.strtolower($orderNumber).'@example.com',
            'phone' => '+628129'.substr(preg_replace('/\D/', '', $orderNumber), -4),
            'type' => User::AUTH_TYPE_PHONE,
            'role' => User::ROLE_BUYER,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'name' => 'Tenant '.$orderNumber,
            'profile_picture_url' => null,
            'rating' => 0,
            'category' => Tenant::CATEGORY_GROCERIES,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produk '.$orderNumber,
            'category' => Tenant::CATEGORY_GROCERIES,
            'price' => 12000,
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => $orderNumber,
            'status' => $status,
            'subtotal_amount' => 24000,
            'delivery_fee' => 5000,
            'total_amount' => 29000,
            'delivery_method' => $deliveryMethodCode === 'pickup' ? 'Ambil ke Toko' : 'Antar Kurir Toko',
            'delivery_method_code' => $deliveryMethodCode,
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'payment_method_code' => 'qr_payment',
            'transaction_at' => now(),
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 12000,
            'line_total' => 24000,
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => $status,
            'title' => 'Status awal',
            'description' => 'Status awal order',
            'sequence' => 1,
            'status_at' => now(),
        ]);

        return $transaction;
    }

    private function createAuthenticatedUser(string $email, string $phone, string $plainTextToken, string $role): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => $email,
            'phone' => $phone,
            'type' => User::AUTH_TYPE_PHONE,
            'role' => $role,
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }

    private function createProductCategory(string $name): ProductCategory
    {
        return ProductCategory::query()->firstOrCreate(
            ['slug' => str($name)->slug()->toString()],
            [
                'name' => $name,
                'image_path' => 'images/ic_'.str($name)->slug('_')->toString().'_category.svg',
            ],
        );
    }

    private function createProductUnit(string $name): ProductUnit
    {
        return ProductUnit::query()->firstOrCreate(
            ['slug' => str($name)->slug()->toString()],
            [
                'name' => $name,
                'is_active' => true,
            ],
        );
    }
}
