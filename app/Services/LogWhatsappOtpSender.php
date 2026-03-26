<?php

namespace App\Services;

use App\Contracts\WhatsappOtpSender;
use Illuminate\Support\Facades\Log;

class LogWhatsappOtpSender implements WhatsappOtpSender
{
    public function send(string $phone, string $otp): void
    {
        Log::info('WhatsApp OTP dispatched.', [
            'phone' => $phone,
            'otp' => $otp,
        ]);
    }
}
