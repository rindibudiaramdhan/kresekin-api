<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OwnerUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OwnerUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_updates_contact_and_assigns_existing_sellers_only_when_run(): void
    {
        $ownerId = (string) Str::uuid();
        $this->ownerConfig($ownerId, 'owner-one@example.com');
        $firstSeller = User::factory()->create(['role' => User::ROLE_SELLER, 'branch_owner_user_id' => null]);

        $this->seed(OwnerUserSeeder::class);

        $this->assertDatabaseHas('users', ['id' => $ownerId, 'role' => User::ROLE_OWNER, 'email' => 'owner-one@example.com']);
        $this->assertSame($ownerId, $firstSeller->fresh()->branch_owner_user_id);

        $futureSeller = User::factory()->create(['role' => User::ROLE_SELLER, 'branch_owner_user_id' => null]);
        $this->assertNull($futureSeller->fresh()->branch_owner_user_id);

        $this->ownerConfig($ownerId, 'owner-updated@example.com');
        $this->seed(OwnerUserSeeder::class);

        $this->assertSame(1, User::query()->where('role', User::ROLE_OWNER)->count());
        $this->assertDatabaseHas('users', ['id' => $ownerId, 'email' => 'owner-updated@example.com']);
        $this->assertSame($ownerId, $futureSeller->fresh()->branch_owner_user_id);
    }

    public function test_seeder_fails_when_stable_uuid_belongs_to_non_owner(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_SELLER]);
        $this->ownerConfig($seller->id, 'owner@example.com');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('role selain owner');
        $this->seed(OwnerUserSeeder::class);
    }

    public function test_seeder_requires_contact_for_primary_login_method(): void
    {
        config()->set('api.owner', [
            'initial_user_id' => (string) Str::uuid(),
            'name' => 'Owner Pertama',
            'email' => null,
            'phone' => '+628123456789',
            'login_type' => 'email',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OWNER_INITIAL_LOGIN_TYPE');
        $this->seed(OwnerUserSeeder::class);
    }

    private function ownerConfig(string $id, string $email): void
    {
        config()->set('api.owner', [
            'initial_user_id' => $id,
            'name' => 'Owner Pertama',
            'email' => $email,
            'phone' => '+628123456789',
            'login_type' => 'email',
        ]);
    }
}
