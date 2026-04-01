<?php

namespace Tests\Feature;

use App\Contracts\WhatsappOtpSender;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSessionToken;
use App\Notifications\LoginOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class RegisterUserApiTest extends TestCase
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
            ->assertJsonPath('data.type', 'email');

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
            ->assertJsonPath('data.type', 'phone');

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
            ->assertJsonPath('data.type', 'email');

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
            ->assertJsonPath('data.type', 'phone');

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
            ->assertJsonPath('data.user.phone', '+6281234567890');

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
            ->assertJsonPath('data.housing_area', 'Komplek Melati Indah')
            ->assertJsonPath('data.address', 'Jl. Mawar No. 10, Blok A2')
            ->assertJsonPath('data.landmark', 'Dekat portal komplek');

        $user->refresh();

        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame('budi@example.com', $user->email);
        $this->assertSame('+628111111111', $user->phone);
        $this->assertSame('Komplek Melati Indah', $user->housing_area);
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

    public function test_authenticated_user_can_get_transaction_history_sorted_by_latest_and_paginated(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $otherUser = User::query()->create([
            'name' => 'Siti',
            'email' => 'siti@example.com',
            'phone' => '+6282222222222',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-history-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 12) as $index) {
            Transaction::query()->create([
                'user_id' => $user->id,
                'order_number' => sprintf('TRX%04d', $index),
                'status' => 'Pesanan Selesai',
                'transaction_at' => now()->subMinutes(12 - $index),
            ]);
        }

        Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => 'Dalam perjalanan',
            'transaction_at' => now()->addMinute(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.order_number', 'TRX0012')
            ->assertJsonPath('data.1.order_number', 'TRX0011')
            ->assertJsonPath('data.9.order_number', 'TRX0003');

        $this->assertCount(10, $response->json('data'));
    }

    public function test_transaction_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/users/transactions');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
