<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResendOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your OTP')
            ->line('Use this OTP to continue.')
            ->line("OTP: {$this->otp}");
    }
}
