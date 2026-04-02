<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_request_updates_last_used_at(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-auth@example.com',
            'phone' => '+6281211111111',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'middleware-valid-token';

        $sessionToken = UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
            'last_used_at' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/payment-methods')
            ->assertOk();

        $this->assertNotNull($sessionToken->fresh()->last_used_at);
    }

    public function test_request_with_expired_session_token_is_unauthenticated(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-expired@example.com',
            'phone' => '+6281211111112',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'middleware-expired-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/payment-methods')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_request_with_orphaned_session_token_is_unauthenticated(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi-orphan@example.com',
            'phone' => '+6281211111113',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'middleware-orphan-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('users')->where('id', $user->id)->delete();
        DB::statement('PRAGMA foreign_keys = ON');

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/payment-methods')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
