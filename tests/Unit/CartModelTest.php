<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\User;
use Tests\TestCase;

class CartModelTest extends TestCase
{
    public function test_it_defines_user_relation(): void
    {
        $relation = (new Cart())->user();

        $this->assertSame(User::class, get_class($relation->getRelated()));
    }
}
