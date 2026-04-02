<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_login_otp_notification_builds_expected_mail_message(): void
    {
        $notification = new LoginOtpNotification('123456');
        $mail = $notification->toMail(new User());

        $this->assertSame(['mail'], $notification->via(new User()));
        $this->assertSame('Your Login OTP', $mail->subject);
        $this->assertSame('Use this OTP to login to your account.', $mail->introLines[0]);
        $this->assertSame('OTP: 123456', $mail->introLines[1]);
    }

    public function test_registration_otp_notification_builds_expected_mail_message(): void
    {
        $notification = new RegistrationOtpNotification('654321');
        $mail = $notification->toMail(new User());

        $this->assertSame(['mail'], $notification->via(new User()));
        $this->assertSame('Your Registration OTP', $mail->subject);
        $this->assertSame('Use this OTP to complete your registration.', $mail->introLines[0]);
        $this->assertSame('OTP: 654321', $mail->introLines[1]);
    }
}
