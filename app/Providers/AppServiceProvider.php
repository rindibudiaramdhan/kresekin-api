<?php

namespace App\Providers;

use App\Contracts\WhatsappOtpSender;
use App\Services\LogWhatsappOtpSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsappOtpSender::class, function () {
            return match (config('services.whatsapp_otp.driver', 'log')) {
                'log' => new LogWhatsappOtpSender,
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
        RateLimiter::for('owner-monitoring', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });
    }
}
