<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResendOtpNotification extends Notification
{
    use BuildsOtpMail, Queueable;

    public function __construct(
        protected string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildOtpMail(
            'Kode OTP Kresek.in',
            'Verifikasi Kresek.in',
            'Gunakan kode verifikasi berikut untuk melanjutkan proses Anda:',
            'Jika Anda tidak merasa meminta kode ini, abaikan email ini',
            $this->otp,
        );
    }
}
