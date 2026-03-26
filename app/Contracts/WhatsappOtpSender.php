<?php

namespace App\Contracts;

interface WhatsappOtpSender
{
    public function send(string $phone, string $otp): void;
}
