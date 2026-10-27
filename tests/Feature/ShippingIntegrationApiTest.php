<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Models\OrderTimeOption;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantShippingSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private User $buyer;

    private Tenant $tenant;

    private Product $product;

    private string $buyerToken;

    private string $sellerToken;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->seller, $this->sellerToken] = $this->account('seller');
        [$this->buyer, $this->buyerToken] = $this->account('buyer');
        $this->buyer->update(['latitude' => 0, 'longitude' => 0.01, 'address' => 'Alamat checkout']);
        $this->tenant = Tenant::query()->create(['owner_user_id' => $this->seller->id, 'name' => 'Toko Ongkir', 'latitude' => 0, 'longitude' => 0, 'is_open' => true]);
        $this->product = $this->product($this->tenant);
        DeliveryMethod::query()->create(['code' => 'store_courier', 'name' => 'Kurir Toko', 'fee' => 2500, 'is_active' => true]);
        DeliveryMethod::query()->create(['code' => 'pickup', 'name' => 'Ambil di Toko', 'fee' => 0, 'requires_order_time' => true, 'is_active' => true]);
        OrderTimeOption::query()->create(['code' => 'sekarang', 'name' => 'Sekarang', 'is_active' => true]);
        PaymentMethod::query()->create(['code' => 'cod', 'name' => 'COD', 'is_active' => true]);
        $this->cart();
        $this->withToken($this->buyerToken);
    }

    private function account(string $role): array
    {
        $token = uniqid('shipping-');
        $user = User::query()->create(['name' => 'User', 'email' => $token.'@example.com', 'role' => $role, 'type' => 'email']);
        UserSessionToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);

        return [$user, $token];
    }

    private function product(Tenant $tenant): Product
    {
        return Product::query()->create(['tenant_id' => $tenant->id, 'name' => 'Produk', 'category' => 'Sayur', 'price' => 10000, 'stock' => 20, 'is_active' => true]);
    }

    private function cart(): void
    {
        Cart::query()->updateOrCreate(['user_id' => $this->buyer->id], ['delivery_method_code' => 'store_courier']);
        CartItem::query()->create(['user_id' => $this->buyer->id, 'product_id' => $this->product->id, 'quantity' => 2]);
    }

    private function settings(array $data): void
    {
        TenantShippingSetting::query()->updateOrCreate(['tenant_id' => $this->tenant->id], ['flat_fee' => null, 'fee_per_km' => null, 'max_distance_km' => null, ...$data]);
    }

    private function checkout(array $extra = [])
    {
        return $this->postJson('/api/checkout', [...['delivery_method_code' => 'store_courier', 'payment_method_code' => 'cod'], ...$extra]);
    }

    public function test_all_modes_match_across_methods_cart_checkout_and_history(): void
    {
        foreach ([
            ['settings' => ['mode' => 'free'], 'fee' => 0],
            ['settings' => ['mode' => 'flat', 'flat_fee' => 5000], 'fee' => 5000],
            ['settings' => ['mode' => 'distance', 'fee_per_km' => 2000], 'fee' => 2224],
        ] as $case) {
            $this->settings($case['settings']);
            $fee = $case['fee'];
            $methods = $this->getJson('/api/delivery-methods')->assertOk()->json('data');
            $this->assertSame($fee, collect($methods)->firstWhere('code', 'store_courier')['fee']);
            $this->patchJson('/api/cart/delivery-method', ['delivery_method_code' => 'store_courier'])->assertOk()->assertJsonPath('data.delivery_method.fee', $fee);
            $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.delivery_fee', $fee)->assertJsonPath('data.grand_total', 20000 + $fee);
            $response = $this->checkout(['delivery_fee' => 1])->assertCreated()->assertJsonPath('data.delivery_fee', $fee)->assertJsonPath('data.total_amount', 20000 + $fee);
            $id = $response->json('data.transaction_id');
            $this->getJson('/api/users/transactions/'.$id)->assertOk()->assertJsonPath('data.delivery_fee', $fee)->assertJsonPath('data.shipping_snapshot.mode', $case['settings']['mode']);
            $this->assertSame($fee, collect($this->getJson('/api/users/transactions')->assertOk()->json('data'))->firstWhere('id', $id)['delivery_fee']);
            $this->cart();
        }
    }

    public function test_rate_and_address_changes_do_not_change_order_or_receipt(): void
    {
        $this->settings(['mode' => 'distance', 'fee_per_km' => 2000]);
        // Tariff changes after the preview must be recalculated at checkout.
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.delivery_fee', 2224);
        $this->settings(['mode' => 'flat', 'flat_fee' => 5000]);
        $response = $this->checkout()->assertCreated()->assertJsonPath('data.delivery_fee', 5000);
        $id = $response->json('data.transaction_id');
        $snapshot = Transaction::query()->findOrFail($id)->shipping_snapshot;
        $this->settings(['mode' => 'free']);
        $this->buyer->update(['latitude' => 10, 'longitude' => 10, 'address' => 'Alamat baru']);
        $this->getJson('/api/users/transactions/'.$id)->assertOk()->assertJsonPath('data.delivery_fee', 5000)->assertJsonPath('data.shipping_snapshot', $snapshot);
        $this->getJson('/api/users/transactions')->assertOk()->assertJsonPath('data.0.shipping_snapshot', $snapshot);
        $this->withToken($this->sellerToken);
        $this->getJson('/api/seller/orders/'.$id)->assertOk()->assertJsonPath('data.seller_delivery_fee', 5000)->assertJsonPath('data.shipping_snapshot', $snapshot);
        $this->getJson('/api/seller/orders/'.$id.'/receipt')->assertOk()->assertJsonPath('data.delivery_fee', 5000)->assertJsonPath('data.total_amount', 25000)->assertJsonPath('data.buyer.address', 'Alamat checkout')->assertJsonPath('data.shipping_snapshot', $snapshot);
        [, $otherSellerToken] = $this->account('seller');
        $this->withToken($otherSellerToken)->getJson('/api/seller/orders/'.$id.'/receipt')->assertNotFound();
        $this->withToken($this->buyerToken)->getJson('/api/seller/orders/'.$id.'/receipt')->assertForbidden();
        $this->cart();
        $this->checkout()->assertCreated()->assertJsonPath('data.delivery_fee', 0);
    }

    public function test_unavailable_shipping_blocks_selection_and_checkout_without_mutation(): void
    {
        $this->settings(['mode' => 'flat', 'flat_fee' => 5000, 'max_distance_km' => 1]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.is_deliverable', false)->assertJsonPath('data.delivery_fee', null)->assertJsonPath('data.grand_total', null);
        $methods = collect($this->getJson('/api/delivery-methods')->assertOk()->json('data'));
        $this->assertFalse($methods->firstWhere('code', 'store_courier')['is_deliverable']);
        $this->assertTrue($methods->firstWhere('code', 'pickup')['is_deliverable']);
        $this->patchJson('/api/cart/delivery-method', ['delivery_method_code' => 'store_courier'])->assertUnprocessable()->assertJsonValidationErrors('delivery_method_code');
        $this->checkout()->assertUnprocessable()->assertJsonValidationErrors('delivery_method_code');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(20, $this->product->fresh()->stock);
        $this->checkout(['delivery_method_code' => 'pickup', 'pickup_time_option' => 'sekarang'])->assertCreated()->assertJsonPath('data.delivery_fee', 0);
    }

    public function test_missing_coordinates_are_not_treated_as_zero(): void
    {
        $this->settings(['mode' => 'distance', 'fee_per_km' => 2000]);
        $this->buyer->update(['latitude' => null]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.is_deliverable', false);
        $this->checkout()->assertUnprocessable();
        $this->buyer->update(['latitude' => 0]);
        $this->tenant->update(['longitude' => null]);
        $this->checkout()->assertUnprocessable();
        $this->settings(['mode' => 'free']);
        $this->checkout()->assertCreated()->assertJsonPath('data.delivery_fee', 0);
    }

    public function test_multiple_stores_cannot_checkout_and_cart_is_preserved(): void
    {
        $otherTenant = Tenant::query()->create(['name' => 'Toko lain', 'is_open' => true]);
        $otherProduct = $this->product($otherTenant);
        CartItem::query()->create(['user_id' => $this->buyer->id, 'product_id' => $otherProduct->id, 'quantity' => 1]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.is_deliverable', false);
        $this->checkout()->assertUnprocessable()->assertJsonValidationErrors('delivery_method_code');
        $this->checkout(['delivery_method_code' => 'pickup', 'pickup_time_option' => 'sekarang'])->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame(20, $this->product->fresh()->stock);
        $this->assertSame(20, $otherProduct->fresh()->stock);
    }

    public function test_legacy_fee_and_historical_order_without_snapshot_are_preserved(): void
    {
        $id = $this->checkout()->assertCreated()->assertJsonPath('data.delivery_fee', 2500)->json('data.transaction_id');
        Transaction::query()->findOrFail($id)->update(['shipping_snapshot' => null]);
        $this->settings(['mode' => 'free']);
        $this->getJson('/api/users/transactions/'.$id)->assertOk()->assertJsonPath('data.delivery_fee', 2500)->assertJsonPath('data.shipping_snapshot', null);
        $this->withToken($this->sellerToken)->getJson('/api/seller/orders/'.$id.'/receipt')->assertOk()->assertJsonPath('data.delivery_fee', 2500)->assertJsonPath('data.shipping_snapshot', null);
    }
}
