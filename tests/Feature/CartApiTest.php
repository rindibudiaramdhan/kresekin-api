<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_product_to_cart_and_view_cart(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        $addResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.quantity', 2);

        $cartResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart');

        $cartResponse
            ->assertOk()
            ->assertJsonPath('data.items.0.product.id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.total_items', 2)
            ->assertJsonPath('data.subtotal', 19998);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_authenticated_user_can_get_product_detail(): void
    {
        [, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct([
            'name' => 'Sawi Putih',
            'price' => 9999,
            'original_price' => 15000,
            'weight_label' => '1kg',
            'description' => 'Sayur segar untuk kebutuhan harian.',
            'delivery_estimate' => '1-2 jam delivery',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/products/'.$product->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Sawi Putih')
            ->assertJsonPath('data.price_label', 'Rp 9.999')
            ->assertJsonPath('data.tenant_name', 'Toko Aminah')
            ->assertJsonPath('data.tenant_rating', 5.0)
            ->assertJsonPath('data.delivery_estimate', '1-2 jam delivery')
            ->assertJsonPath('data.description', 'Sayur segar untuk kebutuhan harian.');
    }

    public function test_authenticated_user_can_update_and_delete_cart_item(): void
    {
        [$user, $token] = $this->createAuthenticatedUser();
        $product = $this->createProduct();

        $cartItem = CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/cart/items/'.$cartItem->id, [
                'quantity' => 4,
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.quantity', 4);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/cart/items/'.$cartItem->id);

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('message', 'Barang berhasil dihapus dari keranjang.');

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_cart_endpoints_require_authentication(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized();

        $this->postJson('/api/cart/items', [
            'product_id' => 1,
            'quantity' => 1,
        ])->assertUnauthorized();
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
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'cart-session-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }

    private function createProduct(array $attributes = []): Product
    {
        $tenant = Tenant::query()->create([
            'name' => 'Toko Aminah',
            'profile_picture_url' => 'https://example.com/aminah.png',
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_VEGETABLES,
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
        ]);

        return Product::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Pakcoy',
            'category' => Tenant::CATEGORY_VEGETABLES,
            'image_url' => 'https://example.com/pakcoy.png',
            'price' => 9999,
            'original_price' => 15000,
            'weight_label' => '500gr',
            'description' => 'Produk segar.',
            'delivery_estimate' => '1-2 jam delivery',
        ], $attributes));
    }
}
