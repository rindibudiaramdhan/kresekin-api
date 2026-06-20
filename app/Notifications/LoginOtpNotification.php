<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
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
            'Kode OTP Masuk Kresek.in',
            'Masuk ke Kresek.in',
            'Gunakan kode verifikasi berikut untuk masuk ke akun Anda:',
            'Jika Anda tidak merasa melakukan login, abaikan email ini',
            $this->otp,
        );
    }
}
