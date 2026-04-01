<?php

namespace App\Services;

use App\Contracts\WhatsappOtpSender;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogWhatsappOtpSender implements WhatsappOtpSender
{
    public function send(string $phone, string $otp): void
    {
        $endpoint = rtrim((string) config('services.whatsapp_otp.base_url'), '/').'/whatsapp/send';
        $payload = [
            'to' => $this->formatPhoneNumber($phone),
            'type' => 'text',
            'text' => sprintf('Kresekin.id - Kode OTP Anda adalah %s', $otp),
        ];

        try {
            $response = Http::timeout((int) config('services.whatsapp_otp.timeout', 10))
                ->withToken((string) config('services.whatsapp_otp.api_key'))
                ->acceptJson()
                ->post($endpoint, $payload)
                ->throw();

            Log::info('WhatsApp OTP dispatched.', [
                'driver' => config('services.whatsapp_otp.driver'),
                'phone' => $phone,
                'provider_phone' => $payload['to'],
                'status' => $response->status(),
            ]);
        } catch (RequestException $exception) {
            Log::error('WhatsApp OTP dispatch failed.', [
                'driver' => config('services.whatsapp_otp.driver'),
                'phone' => $phone,
                'provider_phone' => $payload['to'],
                'endpoint' => $endpoint,
                'status' => $exception->response?->status(),
                'response' => $exception->response?->json(),
            ]);

            throw $exception;
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '62')) {
            return '0'.substr($normalized, 2);
        }

        if (str_starts_with($normalized, '8')) {
            return '0'.$normalized;
        }

        return $normalized;
    }
}
