<?php

namespace App\Notifications;

use App\Models\User;
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
        $isSeller = $notifiable instanceof User && $notifiable->role === User::ROLE_SELLER;

        return $this->buildOtpMail(
            $isSeller ? 'Kode OTP Kresek.in Seller' : 'Kode OTP Kresek.in',
            $isSeller ? 'Verifikasi Kresek.in Seller' : 'Verifikasi Kresek.in',
            'Gunakan kode verifikasi berikut untuk melanjutkan proses Anda:',
            'Jika Anda tidak merasa meminta kode ini, abaikan email ini',
            $this->otp,
            ['isSeller' => $isSeller],
        );
    }
}
