<?php

namespace Tests\Feature;

use App\Services\LogWhatsappOtpSender;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsappOtpSenderTest extends TestCase
{
    public function test_it_sends_whatsapp_otp_to_provider(): void
    {
        config()->set('services.whatsapp_otp.driver', 'saga');
        config()->set('services.whatsapp_otp.base_url', 'https://api.saga-gateway.id');
        config()->set('services.whatsapp_otp.api_key', 'secret-key');
        config()->set('services.whatsapp_otp.timeout', 10);

        Http::fake([
            'https://api.saga-gateway.id/whatsapp/send' => Http::response(['ok' => true], 200),
        ]);
        Log::spy();

        (new LogWhatsappOtpSender())->send('+6281234567890', '123456');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.saga-gateway.id/whatsapp/send'
                && $request->hasHeader('Authorization', 'Bearer secret-key')
                && $request['to'] === '081234567890'
                && $request['type'] === 'text';
        });
    }

    public function test_it_logs_and_throws_when_provider_fails(): void
    {
        config()->set('services.whatsapp_otp.driver', 'saga');
        config()->set('services.whatsapp_otp.base_url', 'https://api.saga-gateway.id');
        config()->set('services.whatsapp_otp.api_key', 'secret-key');
        config()->set('services.whatsapp_otp.timeout', 10);

        Http::fake([
            'https://api.saga-gateway.id/whatsapp/send' => Http::response(['message' => 'failed'], 422),
        ]);
        Log::spy();

        $this->expectException(RequestException::class);

        try {
            (new LogWhatsappOtpSender())->send('+6281234567890', '123456');
        } finally {
            Log::shouldHaveReceived('error')->once();
        }
    }
}
