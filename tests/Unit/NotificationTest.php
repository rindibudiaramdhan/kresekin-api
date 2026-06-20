<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use App\Notifications\ResendOtpNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_login_otp_notification_builds_expected_mail_message(): void
    {
        $notification = new LoginOtpNotification('123456');
        $mail = $notification->toMail(new User);

        $this->assertSame(['mail'], $notification->via(new User));
        $this->assertSame('Kode OTP Masuk Kresek.in', $mail->subject);
        $this->assertSame(['html' => 'emails.otp', 'text' => 'emails.otp-text'], $mail->view);
        $this->assertSame('Masuk ke Kresek.in', $mail->viewData['heading']);
        $this->assertSame('Gunakan kode verifikasi berikut untuk masuk ke akun Anda:', $mail->viewData['intro']);
        $this->assertSame('Jika Anda tidak merasa melakukan login, abaikan email ini', $mail->viewData['ignoreMessage']);
        $this->assertSame('123456', $mail->viewData['otp']);
        $this->assertSame(5, $mail->viewData['expiresInMinutes']);
        $this->assertSame('cs-support@kresek.in', $mail->viewData['supportEmail']);
    }

    public function test_registration_otp_notification_builds_expected_mail_message(): void
    {
        $notification = new RegistrationOtpNotification('654321');
        $mail = $notification->toMail(new User);

        $this->assertSame(['mail'], $notification->via(new User));
        $this->assertSame('Kode OTP Registrasi Kresek.in', $mail->subject);
        $this->assertSame(['html' => 'emails.otp', 'text' => 'emails.otp-text'], $mail->view);
        $this->assertSame('Verifikasi Akun Kresek.in', $mail->viewData['heading']);
        $this->assertSame('Gunakan kode verifikasi berikut untuk menyelesaikan pendaftaran akun Anda:', $mail->viewData['intro']);
        $this->assertSame('Jika Anda tidak merasa melakukan pendaftaran, abaikan email ini', $mail->viewData['ignoreMessage']);
        $this->assertSame('654321', $mail->viewData['otp']);
        $this->assertSame(5, $mail->viewData['expiresInMinutes']);
        $this->assertSame('cs-support@kresek.in', $mail->viewData['supportEmail']);
    }

    public function test_resend_otp_notification_builds_expected_mail_message(): void
    {
        $notification = new ResendOtpNotification('987654');
        $mail = $notification->toMail(new User);

        $this->assertSame(['mail'], $notification->via(new User));
        $this->assertSame('Kode OTP Kresek.in', $mail->subject);
        $this->assertSame(['html' => 'emails.otp', 'text' => 'emails.otp-text'], $mail->view);
        $this->assertSame('Verifikasi Kresek.in', $mail->viewData['heading']);
        $this->assertSame('Gunakan kode verifikasi berikut untuk melanjutkan proses Anda:', $mail->viewData['intro']);
        $this->assertSame('Jika Anda tidak merasa meminta kode ini, abaikan email ini', $mail->viewData['ignoreMessage']);
        $this->assertSame('987654', $mail->viewData['otp']);
        $this->assertSame(5, $mail->viewData['expiresInMinutes']);
        $this->assertSame('cs-support@kresek.in', $mail->viewData['supportEmail']);
    }
}
