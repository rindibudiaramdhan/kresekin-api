<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ModelRelationAndCastTest extends TestCase
{
    public function test_cart_has_user_relation(): void
    {
        $relation = (new Cart())->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function test_cart_item_has_user_and_product_relations_and_quantity_cast(): void
    {
        $cartItem = new CartItem();
        $cartItem->forceFill(['quantity' => '2']);

        $this->assertInstanceOf(BelongsTo::class, $cartItem->user());
        $this->assertInstanceOf(User::class, $cartItem->user()->getRelated());
        $this->assertInstanceOf(BelongsTo::class, $cartItem->product());
        $this->assertInstanceOf(Product::class, $cartItem->product()->getRelated());
        $this->assertSame(2, $cartItem->quantity);
    }

    public function test_product_has_tenant_relation_and_money_casts(): void
    {
        $product = new Product();
        $product->forceFill([
            'price' => '9999',
            'original_price' => '15000',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $product->tenant());
        $this->assertInstanceOf(Tenant::class, $product->tenant()->getRelated());
        $this->assertSame(9999, $product->price);
        $this->assertSame(15000, $product->original_price);
    }

    public function test_user_has_expected_relations_and_attribute_casts(): void
    {
        $user = new User();
        $user->forceFill([
            'latitude' => '-6.2',
            'longitude' => '106.8',
        ]);

        $this->assertInstanceOf(HasMany::class, $user->sessionTokens());
        $this->assertInstanceOf(UserSessionToken::class, $user->sessionTokens()->getRelated());
        $this->assertInstanceOf(HasMany::class, $user->transactions());
        $this->assertInstanceOf(Transaction::class, $user->transactions()->getRelated());
        $this->assertInstanceOf(HasMany::class, $user->cartItems());
        $this->assertInstanceOf(CartItem::class, $user->cartItems()->getRelated());
        $this->assertInstanceOf(HasOne::class, $user->cart());
        $this->assertInstanceOf(Cart::class, $user->cart()->getRelated());
        $this->assertSame(-6.2, $user->latitude);
        $this->assertSame(106.8, $user->longitude);
    }

    public function test_transaction_has_expected_relations_and_amount_casts(): void
    {
        $transaction = new Transaction();
        $transaction->forceFill([
            'subtotal_amount' => '10000',
            'delivery_fee' => '2500',
            'total_amount' => '12500',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $transaction->user());
        $this->assertInstanceOf(User::class, $transaction->user()->getRelated());
        $this->assertInstanceOf(HasMany::class, $transaction->statusHistories());
        $this->assertInstanceOf(HasMany::class, $transaction->items());
        $this->assertInstanceOf(TransactionItem::class, $transaction->items()->getRelated());
        $this->assertSame(10000, $transaction->subtotal_amount);
        $this->assertSame(2500, $transaction->delivery_fee);
        $this->assertSame(12500, $transaction->total_amount);
    }

    public function test_transaction_item_has_expected_relations_and_numeric_casts(): void
    {
        $transactionItem = new TransactionItem();
        $transactionItem->forceFill([
            'quantity' => '3',
            'unit_price' => '5000',
            'line_total' => '15000',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $transactionItem->transaction());
        $this->assertInstanceOf(Transaction::class, $transactionItem->transaction()->getRelated());
        $this->assertInstanceOf(BelongsTo::class, $transactionItem->product());
        $this->assertInstanceOf(Product::class, $transactionItem->product()->getRelated());
        $this->assertInstanceOf(BelongsTo::class, $transactionItem->tenant());
        $this->assertInstanceOf(Tenant::class, $transactionItem->tenant()->getRelated());
        $this->assertSame(3, $transactionItem->quantity);
        $this->assertSame(5000, $transactionItem->unit_price);
        $this->assertSame(15000, $transactionItem->line_total);
    }

    public function test_user_session_token_has_user_relation(): void
    {
        $relation = (new UserSessionToken())->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }
}
