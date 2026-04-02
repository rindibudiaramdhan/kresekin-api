<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

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
                'payment_method_code' => 'bank_transfer',
                'payment_method_option_code' => 'bca',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', Transaction::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.subtotal_amount', 19998)
            ->assertJsonPath('data.delivery_fee', 2500)
            ->assertJsonPath('data.total_amount', 22498)
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.payment_method', 'Transfer Bank')
            ->assertJsonPath('data.payment_method_option_name', 'BCA');

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

    public function test_checkout_requires_delivery_method_selected_in_cart(): void
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
            ->assertJsonPath('message', 'Metode pengiriman belum dipilih di keranjang.');
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
                'payment_method_code' => 'qr_payment',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Keranjang kosong.');
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout', [
            'payment_method_code' => 'cod',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
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
