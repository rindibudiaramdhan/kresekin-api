<?php

namespace App\Notifications;

use App\Models\User;
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
        $isSeller = $notifiable instanceof User && $notifiable->role === User::ROLE_SELLER;

        return $this->buildOtpMail(
            $isSeller ? 'Kode OTP Registrasi Kresek.in Seller' : 'Kode OTP Registrasi Kresek.in',
            $isSeller ? 'Verifikasi Akun Kresek.in Seller' : 'Verifikasi Akun Anda',
            'Masukkan kode berikut untuk menyelesaikan pendaftaran:',
            $isSeller ? 'Selamat datang di kresek.in seller' : 'Selamat datang di kresek.in',
            $this->otp,
            [
                'isSeller' => $isSeller,
                'noteSuffix' => 'Gunakan kode ini untuk mengaktifkan akun Anda.',
            ],
        );
    }
}
