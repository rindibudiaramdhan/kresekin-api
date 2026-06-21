<?php

namespace App\Notifications;

use App\Models\User;
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
        $isSeller = $notifiable instanceof User && $notifiable->role === User::ROLE_SELLER;

        return $this->buildOtpMail(
            $isSeller ? 'Kode OTP Masuk Kresek.in Seller' : 'Kode OTP Masuk Kresek.in',
            $isSeller ? 'Masuk ke Kresek.in Seller' : 'Masuk ke Kresek.in',
            'Gunakan kode verifikasi berikut untuk masuk ke akun Anda:',
            'Jika Anda tidak merasa melakukan login, abaikan email ini',
            $this->otp,
            ['isSeller' => $isSeller],
        );
    }
}
