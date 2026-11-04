<?php

namespace Tests\Feature;

use App\Models\HousingArea;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class UserProfileHousingAreasApiTest extends TestCase
{
    use RefreshDatabase;

    public static function profileRoles(): array
    {
        return [['buyer'], ['seller']];
    }

    #[DataProvider('profileRoles')]
    public function test_multiple_areas_are_saved_retrieved_and_replaced(string $role): void
    {
        $user = $this->authenticate($role);
        $areas = collect(['A', 'B', 'C'])->map(fn ($code) => HousingArea::query()->create([
            'name' => 'Area '.$code, 'code' => $code,
        ]));
        $payload = ['name' => 'Budi', 'email' => $user->email, 'address' => 'Alamat rumah'];

        $this->putJson('/api/users/profile', $payload + ['housing_area_ids' => [$areas[0]->id, $areas[1]->id]])
            ->assertOk()->assertJsonCount(2, 'data.housing_areas')
            ->assertJsonPath('data.housing_area.id', $areas[0]->id)
            ->assertJsonMissingPath('data.housing_areas.0.pivot');
        $response = $this->getJson('/api/users/profile')->assertOk()->assertJsonCount(2, 'data.housing_areas');
        $this->assertEqualsCanonicalizing([$areas[0]->id, $areas[1]->id], array_column($response->json('data.housing_areas'), 'id'));

        $this->putJson('/api/users/profile', $payload + ['housing_area_ids' => [$areas[2]->id]])
            ->assertOk()->assertJsonCount(1, 'data.housing_areas')
            ->assertJsonPath('data.housing_area.id', $areas[2]->id);
        $this->getJson('/api/users/profile')->assertOk()
            ->assertJsonCount(1, 'data.housing_areas')->assertJsonPath('data.housing_areas.0.id', $areas[2]->id);
        $this->assertDatabaseCount('housing_area_user', 1);
        $this->assertSame($areas[2]->id, $user->fresh()->housing_area_id);

        // Legacy clients replace the selection with a single area too.
        $this->putJson('/api/users/profile', $payload + ['housing_area_id' => $areas[1]->id])
            ->assertOk()->assertJsonCount(1, 'data.housing_areas')
            ->assertJsonPath('data.housing_areas.0.id', $areas[1]->id);
        $this->getJson('/api/users/profile')->assertOk()->assertJsonPath('data.housing_areas.0.id', $areas[1]->id);
        $this->assertDatabaseCount('housing_area_user', 1);
    }

    public function test_invalid_area_selections_do_not_change_the_profile(): void
    {
        $user = $this->authenticate('buyer');
        $area = HousingArea::query()->create(['name' => 'Area A', 'code' => 'A']);
        $payload = ['name' => 'Budi', 'email' => $user->email, 'address' => 'Alamat rumah'];
        $this->putJson('/api/users/profile', $payload + ['housing_area_ids' => [$area->id]])->assertOk();

        foreach ([[], null, 'invalid', ['invalid'], [fake()->uuid()], [$area->id, $area->id], ['area' => $area->id]] as $selection) {
            $this->putJson('/api/users/profile', array_merge($payload, ['name' => 'Tidak disimpan', 'housing_area_ids' => $selection]))
                ->assertUnprocessable();
        }
        $this->putJson('/api/users/profile', $payload + ['housing_area_id' => $area->id, 'housing_area_ids' => [$area->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('housing_area_id');
        $this->assertSame('Budi', $user->fresh()->name);
        $this->assertDatabaseCount('housing_area_user', 1);
        $this->getJson('/api/users/profile')->assertOk()->assertJsonPath('data.housing_areas.0.id', $area->id);
    }

    public function test_get_profile_handles_legacy_single_area_and_no_area(): void
    {
        $user = $this->authenticate('seller');
        $this->getJson('/api/users/profile')->assertOk()->assertJsonPath('data.housing_area', null)->assertJsonPath('data.housing_areas', []);
        $area = HousingArea::query()->create(['name' => 'Legacy area', 'code' => 'legacy']);
        $user->forceFill(['housing_area_id' => $area->id])->save();
        $this->getJson('/api/users/profile')->assertOk()
            ->assertJsonPath('data.housing_area.id', $area->id)->assertJsonPath('data.housing_areas.0.id', $area->id);
    }

    public function test_profile_and_areas_roll_back_if_relation_write_fails(): void
    {
        $user = $this->authenticate('buyer');
        $oldArea = HousingArea::query()->create(['name' => 'Old area', 'code' => 'old']);
        $newArea = HousingArea::query()->create(['name' => 'New area', 'code' => 'new']);
        $payload = ['name' => 'Budi', 'email' => $user->email, 'address' => 'Alamat rumah'];
        $this->putJson('/api/users/profile', $payload + ['housing_area_ids' => [$oldArea->id]])->assertOk();
        $failWrite = true;
        DB::listen(function (QueryExecuted $query) use (&$failWrite): void {
            if ($failWrite && str_starts_with(strtolower($query->sql), 'insert') && str_contains($query->sql, 'housing_area_user')) {
                $failWrite = false;
                throw new RuntimeException('Simulated profile area write failure');
            }
        });
        $this->putJson('/api/users/profile', array_merge($payload, [
            'name' => 'Tidak disimpan', 'housing_area_ids' => [$newArea->id],
        ]))->assertStatus(500);
        $this->assertFalse($failWrite);
        $this->assertSame('Budi', $user->fresh()->name);
        $this->assertSame($oldArea->id, $user->fresh()->housing_area_id);
        $this->assertSame([$oldArea->id], $user->housingAreas->modelKeys());
    }

    public function test_migration_backfills_existing_profile_areas(): void
    {
        $migration = require database_path('migrations/2026_11_05_000001_create_housing_area_user_table.php');
        $migration->down();
        $area = HousingArea::query()->create(['name' => 'Existing area', 'code' => 'existing']);
        $user = User::factory()->create(['housing_area_id' => $area->id]);
        User::factory()->create(['housing_area_id' => null]);
        $migration->up();
        $this->assertDatabaseCount('housing_area_user', 1);
        $this->assertDatabaseHas('housing_area_user', ['user_id' => $user->id, 'housing_area_id' => $area->id]);
        $this->assertSame([$area->id], $user->housingAreas->modelKeys());
    }

    private function authenticate(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'housing_area_id' => null]);
        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'profile-areas-token'),
            'expires_at' => now()->addDays(30),
        ]);
        $this->withToken('profile-areas-token');

        return $user;
    }
}
