<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

trait BuildsOtpMail
{
    /**
     * @param  array<string, mixed>  $options
     */
    protected function buildOtpMail(string $subject, string $heading, string $intro, string $closingMessage, string $otp, array $options = []): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.otp', $this->otpMailData($subject, $heading, $intro, $closingMessage, $otp, $options))
            ->text('emails.otp-text', $this->otpMailData($subject, $heading, $intro, $closingMessage, $otp, $options));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function otpMailData(string $subject, string $heading, string $intro, string $closingMessage, string $otp, array $options): array
    {
        $isSeller = (bool) ($options['isSeller'] ?? false);

        return [
            'subject' => $subject,
            'heading' => $heading,
            'intro' => $intro,
            'closingMessage' => $closingMessage,
            'noteSuffix' => $options['noteSuffix'] ?? null,
            'isSeller' => $isSeller,
            'brandLabel' => $isSeller ? 'Seller' : null,
            'downloadLabel' => $isSeller ? 'kresek.in seller' : 'kresek.in',
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
