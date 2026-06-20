<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

trait BuildsOtpMail
{
    protected function buildOtpMail(string $subject, string $heading, string $intro, string $ignoreMessage, string $otp): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.otp', $this->otpMailData($subject, $heading, $intro, $ignoreMessage, $otp))
            ->text('emails.otp-text', $this->otpMailData($subject, $heading, $intro, $ignoreMessage, $otp));
    }

    /**
     * @return array<string, mixed>
     */
    private function otpMailData(string $subject, string $heading, string $intro, string $ignoreMessage, string $otp): array
    {
        return [
            'subject' => $subject,
            'heading' => $heading,
            'intro' => $intro,
            'ignoreMessage' => $ignoreMessage,
            'otp' => $otp,
            'expiresInMinutes' => User::OTP_EXPIRES_IN_MINUTES,
            'supportEmail' => config('mail.otp.support_email'),
            'playstoreUrl' => config('mail.otp.playstore_url'),
            'brandMarkUrl' => asset('images/kresek-bag-icon.svg'),
            'wordmarkUrl' => asset('images/kresek-wordmark.svg'),
            'playstoreBadgeUrl' => asset('images/playstore-badge.svg'),
        ];
    }
}
