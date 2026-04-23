<?php

namespace Tests\Feature;

use App\Contracts\WhatsappOtpSender;
use App\Models\User;
use App\Models\UserSessionToken;
use App\Notifications\LoginOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email_and_receive_otp_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/users/register', [
            'type' => 'email',
            'email' => 'user@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'user@example.com')
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.type', 'email')
            ->assertJsonPath('data.role', 'buyer');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_sent_at);
        $this->assertNull($user->phone);

        Notification::assertSentTo($user, RegistrationOtpNotification::class);
    }

    public function test_user_can_register_with_phone_and_receive_otp_via_whatsapp_sender(): void
    {
        $whatsappOtpSender = Mockery::mock(WhatsappOtpSender::class);
        $whatsappOtpSender
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $phone, string $otp): bool => $phone === '+6281234567890' && preg_match('/^\d{6}$/', $otp) === 1);

        $this->app->instance(WhatsappOtpSender::class, $whatsappOtpSender);

        $response = $this->postJson('/api/users/register', [
            'type' => 'phone',
            'phone' => '+6281234567890',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.phone', '+6281234567890')
            ->assertJsonPath('data.type', 'phone')
            ->assertJsonPath('data.role', 'buyer');

        $user = User::query()->where('phone', '+6281234567890')->firstOrFail();

        $this->assertNull($user->email);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_sent_at);
    }

    public function test_registration_requires_email_when_type_is_email(): void
    {
        $response = $this->postJson('/api/users/register', [
            'type' => 'email',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_phone_when_type_is_phone(): void
    {
        $response = $this->postJson('/api/users/register', [
            'type' => 'phone',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_user_can_login_with_email_and_receive_otp_notification(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'user@example.com',
            'phone' => null,
            'type' => 'email',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $response = $this->postJson('/api/users/login', [
            'type' => 'email',
            'email' => 'user@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'user@example.com')
            ->assertJsonPath('data.type', 'email')
            ->assertJsonPath('data.role', 'buyer');

        $user->refresh();
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_sent_at);

        Notification::assertSentTo($user, LoginOtpNotification::class);
    }

    public function test_user_can_login_with_phone_and_receive_otp_via_whatsapp_sender(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => null,
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $whatsappOtpSender = Mockery::mock(WhatsappOtpSender::class);
        $whatsappOtpSender
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $phone, string $otp): bool => $phone === '+6281234567890' && preg_match('/^\d{6}$/', $otp) === 1);

        $this->app->instance(WhatsappOtpSender::class, $whatsappOtpSender);

        $response = $this->postJson('/api/users/login', [
            'type' => 'phone',
            'phone' => '+6281234567890',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', '+6281234567890')
            ->assertJsonPath('data.type', 'phone')
            ->assertJsonPath('data.role', 'buyer');

        $user->refresh();
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_sent_at);
    }

    public function test_login_returns_not_found_when_user_does_not_exist(): void
    {
        $response = $this->postJson('/api/users/login', [
            'type' => 'phone',
            'phone' => '+6281234567890',
        ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'User tidak ditemukan.');
    }

    public function test_user_can_verify_phone_otp_and_receive_session_token(): void
    {
        $user = User::query()->create([
            'name' => null,
            'email' => null,
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => Hash::make('123456'),
            'otp_sent_at' => now(),
        ]);

        $response = $this->postJson('/api/users/verify-otp', [
            'type' => 'phone',
            'phone' => '+6281234567890',
            'otp' => '123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.phone', '+6281234567890')
            ->assertJsonPath('data.user.role', 'buyer');

        $user->refresh();
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_sent_at);

        $plainTextToken = $response->json('data.token');
        $sessionToken = UserSessionToken::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($plainTextToken);
        $this->assertNotNull($sessionToken);
        $this->assertSame(hash('sha256', $plainTextToken), $sessionToken->token);
    }

    public function test_verify_otp_returns_error_when_code_is_invalid(): void
    {
        User::query()->create([
            'name' => null,
            'email' => null,
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => Hash::make('123456'),
            'otp_sent_at' => now(),
        ]);

        $response = $this->postJson('/api/users/verify-otp', [
            'type' => 'phone',
            'phone' => '+6281234567890',
            'otp' => '654321',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kode OTP tidak valid.');

        $this->assertDatabaseCount('user_session_tokens', 0);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::query()->create([
            'name' => null,
            'email' => 'old@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'session-token-for-test';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->putJson('/api/users/profile', [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'phone' => '+628111111111',
                'housing_area' => 'Komplek Melati Indah',
                'address' => 'Jl. Mawar No. 10, Blok A2',
                'landmark' => 'Dekat portal komplek',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.phone', '+628111111111')
            ->assertJsonPath('data.role', 'buyer')
            ->assertJsonPath('data.housing_area', 'Komplek Melati Indah')
            ->assertJsonPath('data.address', 'Jl. Mawar No. 10, Blok A2')
            ->assertJsonPath('data.landmark', 'Dekat portal komplek');

        $user->refresh();

        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame('budi@example.com', $user->email);
        $this->assertSame('+628111111111', $user->phone);
        $this->assertSame('Komplek Melati Indah', $user->housingArea->name);
        $this->assertSame('Jl. Mawar No. 10, Blok A2', $user->address);
        $this->assertSame('Dekat portal komplek', $user->landmark);
    }

    public function test_update_profile_requires_authentication(): void
    {
        $response = $this->putJson('/api/users/profile', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'housing_area' => 'Komplek Melati Indah',
            'address' => 'Jl. Mawar No. 10, Blok A2',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_update_profile_requires_required_fields_from_form(): void
    {
        $user = User::query()->create([
            'name' => null,
            'email' => 'old@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'session-token-for-validation';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->putJson('/api/users/profile', [
                'phone' => '+628111111111',
                'landmark' => 'Dekat portal komplek',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'housing_area', 'address']);
    }

    public function test_authenticated_user_can_refresh_session_token(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'refresh@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $oldPlainTextToken = 'old-session-token-for-refresh';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $oldPlainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$oldPlainTextToken)
            ->postJson('/api/users/refresh-session');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Sesi login berhasil diperbarui.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'refresh@example.com')
            ->assertJsonPath('data.user.role', 'buyer');

        $newPlainTextToken = $response->json('data.token');

        $this->assertNotSame($oldPlainTextToken, $newPlainTextToken);
        $this->assertDatabaseMissing('user_session_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $oldPlainTextToken),
        ]);
        $this->assertDatabaseHas('user_session_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $newPlainTextToken),
        ]);
        $this->assertSame(1, UserSessionToken::query()->where('user_id', $user->id)->count());
    }

    public function test_refresh_session_requires_authentication(): void
    {
        $response = $this->postJson('/api/users/refresh-session');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_can_register_as_seller(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/users/register', [
            'type' => 'email',
            'role' => 'seller',
            'email' => 'seller@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'seller@example.com')
            ->assertJsonPath('data.role', 'seller');

        $this->assertDatabaseHas('users', [
            'email' => 'seller@example.com',
            'role' => 'seller',
        ]);
    }
}
