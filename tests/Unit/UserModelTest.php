<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSessionToken;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_it_defines_expected_casts(): void
    {
        $casts = (new User())->getCasts();

        $this->assertSame('datetime', $casts['email_verified_at']);
        $this->assertSame('hashed', $casts['password']);
        $this->assertSame('datetime', $casts['otp_sent_at']);
        $this->assertSame('float', $casts['latitude']);
        $this->assertSame('float', $casts['longitude']);
    }

    public function test_it_defines_expected_relations(): void
    {
        $user = new User();

        $this->assertSame(UserSessionToken::class, get_class($user->sessionTokens()->getRelated()));
        $this->assertSame(Transaction::class, get_class($user->transactions()->getRelated()));
        $this->assertSame(CartItem::class, get_class($user->cartItems()->getRelated()));
        $this->assertSame(Cart::class, get_class($user->cart()->getRelated()));
    }
}
