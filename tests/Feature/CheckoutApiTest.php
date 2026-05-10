<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Models\OrderTimeOption;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OrderTimeOption::query()->create([
            'code' => 'sekarang',
            'name' => 'Sekarang',
            'description' => 'estimasi 15-30 menit',
            'requires_schedule' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        OrderTimeOption::query()->create([
            'code' => 'jadwalkan',
            'name' => 'Jadwalkan',
            'description' => null,
            'requires_schedule' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $bankTransfer = PaymentMethod::query()->create([
            'code' => PaymentMethod::BANK_TRANSFER,
            'name' => 'Transfer Bank',
            'icon_key' => 'bank_transfer',
            'requires_option' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $bankTransfer->options()->createMany([
            [
                'code' => 'bca',
                'name' => 'BCA',
                'icon_key' => 'bank_bca',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'mandiri',
                'name' => 'Mandiri',
                'icon_key' => 'bank_mandiri',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ]);

        PaymentMethod::query()->create([
            'code' => PaymentMethod::QR_PAYMENT,
            'name' => 'QR Payment',
            'icon_key' => 'qris',
            'requires_option' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        PaymentMethod::query()->create([
            'code' => PaymentMethod::COD,
            'name' => 'COD',
            'icon_key' => 'cod',
            'requires_option' => false,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        DeliveryMethod::query()->create([
            'code' => 'store_courier',
            'name' => 'Antar Kurir Toko',
            'description' => 'Diantar hari ini',
            'fee' => 2500,
            'requires_order_time' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        DeliveryMethod::query()->create([
            'code' => 'pickup',
            'name' => 'Ambil ke Toko',
            'description' => null,
            'fee' => 0,
            'requires_order_time' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_checkout_cart_into_transaction(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'bank_transfer',
                'payment_method_option_code' => 'bca',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', Transaction::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.subtotal_amount', 19998)
            ->assertJsonPath('data.delivery_fee', 2500)
            ->assertJsonPath('data.discount_amount', 0)
            ->assertJsonPath('data.total_amount', 22498)
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.payment_method', 'Transfer Bank')
            ->assertJsonPath('data.payment_method_option_name', 'BCA')
            ->assertJsonPath('data.promo_code', null);

        $transaction = Transaction::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($transaction);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'line_total' => 19998,
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'delivery_method_code' => null,
        ]);
    }

    public function test_checkout_can_apply_optional_promo_code(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();
        $promoCode = PromoCode::query()->create([
            'code' => 'HEMAT10',
            'name' => 'Hemat 10%',
            'description' => 'Diskon 10% untuk pesanan minimal Rp 10.000.',
            'discount_type' => PromoCode::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_order_amount' => 10000,
            'maximum_discount_amount' => 10000,
            'quantity' => 5,
            'used_quantity' => 2,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'bank_transfer',
                'payment_method_option_code' => 'bca',
                'promo_code' => 'hemat10',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.subtotal_amount', 19998)
            ->assertJsonPath('data.delivery_fee', 2500)
            ->assertJsonPath('data.discount_amount', 1999)
            ->assertJsonPath('data.discount_amount_label', 'Rp 1.999')
            ->assertJsonPath('data.total_amount', 20499)
            ->assertJsonPath('data.promo_code', 'HEMAT10')
            ->assertJsonPath('data.promo_name', 'Hemat 10%')
            ->assertJsonPath('data.promo_discount_type', PromoCode::DISCOUNT_TYPE_PERCENTAGE)
            ->assertJsonPath('data.promo_discount_value', 10);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'promo_code_id' => $promoCode->id,
            'promo_code' => 'HEMAT10',
            'promo_name' => 'Hemat 10%',
            'discount_amount' => 1999,
            'total_amount' => 20499,
        ]);
        $this->assertSame(3, $promoCode->fresh()->used_quantity);
    }

    public function test_checkout_rejects_unknown_promo_code(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'qr_payment',
                'promo_code' => 'TIDAKADA',
            ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Promo tidak ditemukan.');
    }

    public function test_checkout_rejects_promo_code_when_minimum_order_is_not_met(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        PromoCode::query()->create([
            'code' => 'MIN50K',
            'name' => 'Minimal 50K',
            'discount_type' => PromoCode::DISCOUNT_TYPE_FIXED_AMOUNT,
            'discount_value' => 5000,
            'minimum_order_amount' => 50000,
            'is_active' => true,
        ]);

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'qr_payment',
                'promo_code' => 'MIN50K',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Minimal belanja untuk promo belum terpenuhi.');
    }

    public function test_checkout_with_pickup_can_store_pickup_time_now(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'pickup',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'pickup',
                'payment_method_code' => 'qr_payment',
                'pickup_time_option' => 'sekarang',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.delivery_method', 'Ambil ke Toko')
            ->assertJsonPath('data.pickup_time_option', 'sekarang')
            ->assertJsonPath('data.pickup_scheduled_at', null)
            ->assertJsonPath('data.delivery_fee', 0);
    }

    public function test_checkout_with_pickup_schedule_requires_pickup_time_payload(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'pickup',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'pickup',
                'payment_method_code' => 'cod',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pickup_time_option']);
    }

    public function test_checkout_with_pickup_schedule_requires_scheduled_time_when_option_is_jadwalkan(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'pickup',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'pickup',
                'payment_method_code' => 'cod',
                'pickup_time_option' => 'jadwalkan',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pickup_scheduled_at']);
    }

    public function test_checkout_with_pickup_schedule_can_store_scheduled_time(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'pickup',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'pickup',
                'payment_method_code' => 'cod',
                'pickup_time_option' => 'jadwalkan',
                'pickup_scheduled_at' => '10:30',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.pickup_time_option', 'jadwalkan')
            ->assertJsonPath('data.pickup_scheduled_at', '10:30');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'pickup_time_option' => 'jadwalkan',
            'pickup_scheduled_at' => '10:30',
        ]);
    }

    public function test_checkout_requires_delivery_method_code(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => null,
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'payment_method_code' => 'qr_payment',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delivery_method_code']);
    }

    public function test_checkout_requires_payment_option_for_bank_transfer(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'bank_transfer',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_option_code']);
    }

    public function test_checkout_rejects_invalid_payment_method_code(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'invalid_method',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_code']);
    }

    public function test_checkout_rejects_invalid_payment_method_option_code(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'bank_transfer',
                'payment_method_option_code' => 'invalid_bank',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method_option_code']);
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();

        Cart::query()->create([
            'user_id' => $user->id,
            'delivery_method_code' => 'store_courier',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/checkout', [
                'delivery_method_code' => 'store_courier',
                'payment_method_code' => 'qr_payment',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Keranjang kosong.');
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout', [
            'delivery_method_code' => 'store_courier',
            'payment_method_code' => 'cod',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    private function createAuthenticatedUser(): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'checkout-session-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }

    private function createProduct(): Product
    {
        $tenant = Tenant::query()->create([
            'name' => 'Toko Aminah',
            'profile_picture_url' => 'https://example.com/aminah.png',
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_VEGETABLES,
        ]);

        return Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pakcoy',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/pakcoy.png',
            'price' => 9999,
            'original_price' => 15000,
            'weight_label' => '500gr',
            'description' => 'Produk segar.',
            'delivery_estimate' => '1-2 jam delivery',
        ]);
    }
}
