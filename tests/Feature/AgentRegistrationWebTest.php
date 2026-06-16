<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgentRegistrationWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_can_render(): void
    {
        $this->get('/agent/register')
            ->assertOk()
            ->assertSee('Informasi Akun')
            ->assertSee('Daftar Sekarang');
    }

    public function test_verify_otp_page_can_render(): void
    {
        $this->get('/agent/verify-otp?email=agent@example.com')
            ->assertOk()
            ->assertSee('Verifikasi OTP')
            ->assertSee('agent@example.com');
    }

    public function test_agent_can_register_with_valid_data(): void
    {
        Notification::fake();
        Storage::fake('local');

        $response = $this->post('/agent/register', $this->validPayload());

        $user = User::query()->where('email', 'agent@example.com')->firstOrFail();

        $response
            ->assertRedirect(route('agent.register.verify-otp', ['email' => 'agent@example.com']))
            ->assertSessionHas('status', 'Registrasi berhasil. Kami telah mengirim OTP untuk verifikasi akun agent Anda.');

        $this->assertSame('Agent Kresek', $user->name);
        $this->assertSame('agent@example.com', $user->email);
        $this->assertSame('+6281234567890', $user->phone);
        $this->assertSame(User::AUTH_TYPE_EMAIL, $user->type);
        $this->assertSame(User::ROLE_AGENT, $user->role);
        $this->assertNotNull($user->agent_code);
        $this->assertNull($user->password);
        $this->assertNotNull($user->identity_document_path);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(User::AGENT_REGISTRATION_TERMS_VERSION, $user->terms_version);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(User::AGENT_VERIFICATION_PENDING_REVIEW, $user->agent_verification_status);
        $this->assertNull($user->agent_verified_at);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_sent_at);

        Storage::disk('local')->assertExists($user->identity_document_path);
        Notification::assertSentTo($user, RegistrationOtpNotification::class);
    }

    public function test_agent_registration_rejects_empty_payload(): void
    {
        $this->from('/agent/register')
            ->post('/agent/register')
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors([
                'name',
                'email',
                'phone',
                'identity_document',
                'consent',
            ]);
    }

    public function test_agent_registration_rejects_duplicate_email_for_agent_role(): void
    {
        User::factory()->create([
            'email' => 'agent@example.com',
            'role' => User::ROLE_AGENT,
        ]);

        $this->from('/agent/register')
            ->post('/agent/register', $this->validPayload())
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors(['email']);
    }

    public function test_agent_registration_rejects_duplicate_phone_for_agent_role(): void
    {
        User::factory()->create([
            'email' => 'other@example.com',
            'phone' => '+6281234567890',
            'role' => User::ROLE_AGENT,
        ]);

        $this->from('/agent/register')
            ->post('/agent/register', $this->validPayload())
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors(['phone']);
    }

    public function test_agent_registration_rejects_invalid_file_type(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload([
            'identity_document' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
        ]);

        $this->from('/agent/register')
            ->post('/agent/register', $payload)
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors(['identity_document']);

        $this->assertDatabaseMissing('users', [
            'email' => 'agent@example.com',
            'role' => User::ROLE_AGENT,
        ]);
    }

    public function test_agent_registration_rejects_file_larger_than_five_mb(): void
    {
        Storage::fake('local');

        $payload = $this->validPayload([
            'identity_document' => UploadedFile::fake()->create('document.pdf', 5121, 'application/pdf'),
        ]);

        $this->from('/agent/register')
            ->post('/agent/register', $payload)
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors(['identity_document']);
    }

    public function test_agent_registration_requires_consent(): void
    {
        $payload = $this->validPayload([
            'consent' => null,
        ]);

        $this->from('/agent/register')
            ->post('/agent/register', $payload)
            ->assertRedirect('/agent/register')
            ->assertSessionHasErrors(['consent']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Agent Kresek',
            'email' => 'agent@example.com',
            'phone' => '+6281234567890',
            'identity_document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'consent' => '1',
        ], $overrides);
    }
}
