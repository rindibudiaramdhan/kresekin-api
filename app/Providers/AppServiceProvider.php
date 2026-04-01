<?php

namespace App\Providers;

use App\Contracts\WhatsappOtpSender;
use App\Services\LogWhatsappOtpSender;
use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsappOtpSender::class, function () {
            return match (config('services.whatsapp_otp.driver', 'log')) {
                'log' => new LogWhatsappOtpSender(),
                default => throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported WhatsApp OTP driver [%s]. Implement a sender for this driver or set WHATSAPP_OTP_DRIVER=log.',
                        config('services.whatsapp_otp.driver')
                    )
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
