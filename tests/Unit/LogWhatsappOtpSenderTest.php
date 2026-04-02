<?php

namespace Tests\Unit;

use App\Services\LogWhatsappOtpSender;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogWhatsappOtpSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp_otp.driver', 'saga');
        config()->set('services.whatsapp_otp.base_url', 'https://api.saga-gateway.id');
        config()->set('services.whatsapp_otp.api_key', 'secret-token');
        config()->set('services.whatsapp_otp.timeout', 10);
    }

    public function test_send_dispatches_whatsapp_otp_successfully(): void
    {
        Http::fake([
            'https://api.saga-gateway.id/whatsapp/send' => Http::response(['ok' => true], 200),
        ]);
        Log::spy();

        (new LogWhatsappOtpSender())->send('+6285134532129', '123455');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.saga-gateway.id/whatsapp/send'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['to'] === '085134532129'
                && $request['type'] === 'text'
                && $request['text'] === 'Kresekin.id - Kode OTP Anda adalah 123455';
        });

        Log::shouldHaveReceived('info')->once();
    }

    public function test_send_logs_and_rethrows_when_provider_returns_error(): void
    {
        Http::fake([
            'https://api.saga-gateway.id/whatsapp/send' => Http::response(['message' => 'failed'], 500),
        ]);
        Log::spy();

        $this->expectException(RequestException::class);

        try {
            (new LogWhatsappOtpSender())->send('085134532129', '123455');
        } finally {
            Log::shouldHaveReceived('error')->once();
        }
    }
}
