<?php

namespace Tests\Feature;

use App\Contracts\WhatsappOtpSender;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
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

    public function test_authenticated_user_can_get_transaction_detail(): void
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
            'phone' => '+628111111111',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-detail-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'order_number' => '26032301CATSYR',
            'status' => Transaction::STATUS_PROCESSING,
            'total_amount' => 9999999,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_BANK_TRANSFER,
            'transaction_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 0),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_PENDING_PAYMENT,
            'title' => 'Pembayaran Transfer Bank Lunas',
            'description' => 'Auto konfirmasi',
            'sequence' => 1,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 2),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_ACCEPTED_BY_STORE,
            'title' => 'Pesanan diterima',
            'description' => 'Langsung diproses',
            'sequence' => 2,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 3),
        ]);

        TransactionStatusHistory::query()->create([
            'transaction_id' => $transaction->id,
            'status' => Transaction::STATUS_PROCESSING,
            'title' => 'Pesanan sedang diproses',
            'description' => 'Toko memproses',
            'sequence' => 3,
            'status_at' => now()->setTimezone('Asia/Jakarta')->setDate(2026, 3, 23)->setTime(10, 5),
        ]);

        $otherTransaction = Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => Transaction::STATUS_COMPLETED,
            'total_amount' => 10000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_QRIS,
            'transaction_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.order_number', '26032301CATSYR')
            ->assertJsonPath('data.status', Transaction::STATUS_PROCESSING)
            ->assertJsonPath('data.status_label', 'Sedang Diproses')
            ->assertJsonPath('data.total_amount', 9999999)
            ->assertJsonPath('data.total_amount_label', 'Rp. 9.999.999')
            ->assertJsonPath('data.delivery_method', 'Antar Kurir Toko')
            ->assertJsonPath('data.payment_method', 'Transfer Bank')
            ->assertJsonPath('data.status_timelines.0.title', 'Pembayaran Transfer Bank Lunas')
            ->assertJsonPath('data.status_timelines.1.title', 'Pesanan diterima')
            ->assertJsonPath('data.status_timelines.2.title', 'Pesanan sedang diproses');

        $this->assertCount(3, $response->json('data.status_timelines'));
        $this->assertNotSame($otherTransaction->id, $response->json('data.id'));
    }

    public function test_transaction_detail_returns_not_found_for_other_users_transaction(): void
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
            'phone' => '+628111111111',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'transaction-detail-forbidden-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'OTHER0001',
            'status' => Transaction::STATUS_CANCELED,
            'total_amount' => 20000,
            'delivery_method' => 'Antar Kurir Toko',
            'payment_method' => Transaction::PAYMENT_METHOD_VIRTUAL_ACCOUNT,
            'transaction_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/users/transactions/'.$transaction->id);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    public function test_authenticated_user_can_get_paginated_tenant_list_with_default_limit(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'tenant-list-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 12) as $index) {
            Tenant::query()->create([
                'name' => sprintf('Tenant %02d', $index),
                'profile_picture_url' => sprintf('https://example.com/tenant-%02d.png', $index),
                'rating' => 4.5,
                'category' => Tenant::CATEGORY_TOILETRIES,
                'latitude' => -6.2000000,
                'longitude' => 106.8200000 + ($index / 1000),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.0.category_slug', 'toiletries')
            ->assertJsonPath('data.0.category_icon_key', 'toiletries')
            ->assertJsonPath('data.0.category_background_color', '#FFF5DF')
            ->assertJsonPath('data.0.category_icon_color', '#F4B544')
            ->assertJsonPath('data.0.rating', 4.5);

        $this->assertCount(10, $response->json('data'));
        $this->assertNotNull($response->json('data.0.distance_km'));
        $this->assertSame('km', substr((string) $response->json('data.0.distance_label'), -2));
    }

    public function test_tenant_list_respects_limit_query_parameter(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'tenant-list-limit-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        foreach (range(1, 5) as $index) {
            Tenant::query()->create([
                'name' => sprintf('Tenant %02d', $index),
                'profile_picture_url' => sprintf('https://example.com/tenant-%02d.png', $index),
                'rating' => 5.0,
                'category' => Tenant::CATEGORY_GROCERIES,
                'latitude' => -6.2100000,
                'longitude' => 106.8260000 + ($index / 1000),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_GROCERIES)
            ->assertJsonPath('data.0.category_slug', 'sembako')
            ->assertJsonPath('data.0.category_icon_key', 'groceries');

        $this->assertCount(3, $response->json('data'));
    }

    public function test_tenant_list_can_be_filtered_by_category_and_sorted_by_nearest_distance(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'tenant-category-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        Tenant::query()->create([
            'name' => 'Toko Far',
            'profile_picture_url' => 'https://example.com/far.png',
            'rating' => 4.2,
            'category' => Tenant::CATEGORY_TOILETRIES,
            'latitude' => -6.2400000,
            'longitude' => 106.8500000,
        ]);

        Tenant::query()->create([
            'name' => 'Toko Near',
            'profile_picture_url' => 'https://example.com/near.png',
            'rating' => 4.9,
            'category' => Tenant::CATEGORY_TOILETRIES,
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
        ]);

        Tenant::query()->create([
            'name' => 'Toko Sembako',
            'profile_picture_url' => 'https://example.com/grocery.png',
            'rating' => 5.0,
            'category' => Tenant::CATEGORY_GROCERIES,
            'latitude' => -6.2005000,
            'longitude' => 106.8165000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?category='.urlencode(Tenant::CATEGORY_TOILETRIES));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Toko Near')
            ->assertJsonPath('data.1.name', 'Toko Far')
            ->assertJsonPath('data.0.category', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.1.category', Tenant::CATEGORY_TOILETRIES);

        $this->assertLessThan(
            $response->json('data.1.distance_km'),
            $response->json('data.0.distance_km')
        );
    }

    public function test_tenant_list_returns_validation_error_for_unknown_category(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'latitude' => -6.2000000,
            'longitude' => 106.8160000,
        ]);

        $plainTextToken = 'tenant-invalid-category-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants?category=Elektronik');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_authenticated_user_can_get_tenant_categories(): void
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

        $plainTextToken = 'tenant-categories-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/tenants/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', Tenant::CATEGORY_VEGETABLES)
            ->assertJsonPath('data.0.slug', 'sayur')
            ->assertJsonPath('data.0.icon_key', 'vegetables')
            ->assertJsonPath('data.0.background_color', '#E7F6EB')
            ->assertJsonPath('data.0.icon_color', '#67B97A')
            ->assertJsonPath('data.3.name', Tenant::CATEGORY_TOILETRIES)
            ->assertJsonPath('data.3.icon_key', 'toiletries')
            ->assertJsonPath('data.14.name', Tenant::CATEGORY_GROCERIES);

        $this->assertCount(count(Tenant::CATEGORIES), $response->json('data'));
    }

    public function test_tenant_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/tenants');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_tenant_categories_requires_authentication(): void
    {
        $response = $this->getJson('/api/tenants/categories');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
