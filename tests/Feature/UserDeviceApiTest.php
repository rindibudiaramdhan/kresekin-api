<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_device(): void
    {
        [$user, $token] = $this->createAuthenticatedUser('device-user@example.com', '+6281211111101', 'device-token-1');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/devices', [
                'device_token' => 'firebase-token-abc',
                'platform' => 'android',
                'device_name' => 'Pixel 8',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.device_token', 'firebase-token-abc')
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.device_name', 'Pixel 8');

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => 'firebase-token-abc',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
        ]);
    }

    public function test_user_can_have_multiple_devices(): void
    {
        [$user, $token] = $this->createAuthenticatedUser('multi-device@example.com', '+6281211111102', 'device-token-2');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/devices', [
                'device_token' => 'firebase-token-1',
                'platform' => 'android',
                'device_name' => 'Samsung A55',
            ])
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/devices', [
                'device_token' => 'firebase-token-2',
                'platform' => 'ios',
                'device_name' => 'iPhone 15',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('user_devices', 2);
        $this->assertSame(2, UserDevice::query()->where('user_id', $user->id)->count());
    }

    public function test_existing_device_token_is_reassigned_and_updated_without_duplication(): void
    {
        [$firstUser] = $this->createAuthenticatedUser('first-device@example.com', '+6281211111103', 'device-token-3');
        [$secondUser, $secondToken] = $this->createAuthenticatedUser('second-device@example.com', '+6281211111104', 'device-token-4');

        $device = UserDevice::query()->create([
            'user_id' => $firstUser->id,
            'device_token' => 'firebase-shared-token',
            'platform' => 'android',
            'device_name' => 'Old Device',
            'last_seen_at' => now()->subDay(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson('/api/users/devices', [
                'device_token' => 'firebase-shared-token',
                'platform' => 'ios',
                'device_name' => 'New Device',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $device->id)
            ->assertJsonPath('data.user_id', $secondUser->id)
            ->assertJsonPath('data.platform', 'ios')
            ->assertJsonPath('data.device_name', 'New Device');

        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', [
            'id' => $device->id,
            'user_id' => $secondUser->id,
            'device_token' => 'firebase-shared-token',
            'platform' => 'ios',
            'device_name' => 'New Device',
        ]);
    }

    public function test_user_device_registration_requires_authentication(): void
    {
        $this->postJson('/api/users/devices', [
            'device_token' => 'firebase-token-unauth',
            'platform' => 'android',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_device_registration_validates_payload(): void
    {
        [, $token] = $this->createAuthenticatedUser('validation-device@example.com', '+6281211111105', 'device-token-5');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/devices', [
                'device_token' => '',
                'platform' => 'windows_phone',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_token', 'platform']);
    }

    private function createAuthenticatedUser(string $email, string $phone, string $plainTextToken): array
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => $email,
            'phone' => $phone,
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }
}
