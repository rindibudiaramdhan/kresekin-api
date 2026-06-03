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
        $this->createOrderForSeller($otherSeller, 'ORDER002', Transaction::STATUS_PROCESSING);

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders');

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.order_number', 'ORDER001')
            ->assertJsonPath('data.0.status_code', Transaction::STATUS_CODE_PROCESSING)
            ->assertJsonPath('meta.total', 1);

        $detailResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/orders/'.$order->id);

        $detailResponse
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', 'ORDER001')
            ->assertJsonPath('data.items.0.product_name', 'Produk ORDER001')
            ->assertJsonPath('data.status_timelines.0.status_code', Transaction::STATUS_CODE_PROCESSING);
    }

    public function test_seller_can_access_split_dashboard_apis_for_own_store(): void
    {
        Carbon::setTestNow('2026-04-02 10:00:00');

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

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/orders-today/counts')
            ->assertOk()
            ->assertJsonPath('data.new.count', 1)
            ->assertJsonPath('data.completed.count', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seller/dashboard/orders/new-preview')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newToday->id)
            ->assertJsonPath('data.0.order_number', 'DASH002')
            ->assertJsonPath('data.0.can_process', true);

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
    }

    public function test_seller_cannot_access_buyer_checkout_endpoint(): void
    {
        [, $token] = $this->createAuthenticatedUser('seller5@example.com', '+6281200000006', 'seller-token-5', User::ROLE_SELLER);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint ini hanya dapat diakses oleh pengguna dengan role buyer.');
    }

    private function createOrderForSeller(User $seller, string $orderNumber, string $status): Transaction
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
            'delivery_method' => 'Diantar',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
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
