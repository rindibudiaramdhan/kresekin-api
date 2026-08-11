<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Notifications\ResendOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OwnerAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_and_resend_login_otp_through_owner_endpoints(): void
    {
        Notification::fake();
        $owner = User::query()->create([
            'name' => 'Owner Pertama',
            'email' => 'owner-login@example.com',
            'phone' => '+628123456789',
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => User::ROLE_OWNER,
        ]);

        $this->postJson('/api/owner/login', [
            'type' => 'email',
            'email' => $owner->email,
        ])->assertOk()->assertJsonPath('data.role', User::ROLE_OWNER);

        Notification::assertSentTo($owner, LoginOtpNotification::class);

        $this->postJson('/api/owner/resend-otp', [
            'type' => 'email',
            'email' => $owner->email,
        ])->assertOk()->assertJsonPath('data.role', User::ROLE_OWNER);

        Notification::assertSentTo($owner, ResendOtpNotification::class);
    }

    public function test_owner_login_does_not_match_same_contact_from_another_role(): void
    {
        User::query()->create([
            'name' => 'Seller',
            'email' => 'shared-contact@example.com',
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => User::ROLE_SELLER,
        ]);

        $this->postJson('/api/owner/login', [
            'type' => 'email',
            'email' => 'shared-contact@example.com',
        ])->assertNotFound();
    }
}
