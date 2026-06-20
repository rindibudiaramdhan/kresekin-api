<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
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
            'Kode OTP Registrasi Kresek.in',
            'Verifikasi Akun Kresek.in',
            'Gunakan kode verifikasi berikut untuk menyelesaikan pendaftaran akun Anda:',
            'Jika Anda tidak merasa melakukan pendaftaran, abaikan email ini',
            $this->otp,
        );
    }
}
