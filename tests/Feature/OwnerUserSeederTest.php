<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OwnerUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_generates_initial_owner_and_is_idempotent(): void
    {
        $firstSeller = User::factory()->create(['role' => User::ROLE_SELLER, 'branch_owner_user_id' => null]);

        $this->seed(OwnerUserSeeder::class);

        $owner = User::query()->where('internal_provisioning_key', 'owner-initial-mvp')->firstOrFail();
        $this->assertSame(User::ROLE_OWNER, $owner->role);
        $this->assertSame(User::AUTH_TYPE_EMAIL, $owner->type);
        $this->assertStringEndsWith('@example.test', $owner->email);
        $this->assertSame($owner->id, $firstSeller->fresh()->branch_owner_user_id);

        $futureSeller = User::factory()->create(['role' => User::ROLE_SELLER, 'branch_owner_user_id' => null]);
        $this->assertNull($futureSeller->fresh()->branch_owner_user_id);

        $this->seed(OwnerUserSeeder::class);

        $this->assertSame(1, User::query()->where('role', User::ROLE_OWNER)->count());
        $this->assertSame($owner->id, $futureSeller->fresh()->branch_owner_user_id);
        $this->assertSame($owner->email, $owner->fresh()->email);
    }

    public function test_seeder_does_not_take_seller_assignment_from_another_owner(): void
    {
        $otherOwner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $assignedSeller = User::factory()->create([
            'role' => User::ROLE_SELLER,
            'branch_owner_user_id' => $otherOwner->id,
        ]);
        $unassignedSeller = User::factory()->create([
            'role' => User::ROLE_SELLER,
            'branch_owner_user_id' => null,
        ]);

        $this->seed(OwnerUserSeeder::class);

        $initialOwner = User::query()->where('internal_provisioning_key', 'owner-initial-mvp')->firstOrFail();
        $this->assertSame($otherOwner->id, $assignedSeller->fresh()->branch_owner_user_id);
        $this->assertSame($initialOwner->id, $unassignedSeller->fresh()->branch_owner_user_id);
        $this->assertSame(2, User::query()->where('role', User::ROLE_OWNER)->count());
    }
}
