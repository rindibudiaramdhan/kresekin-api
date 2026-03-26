<?php

namespace App\Providers;

use App\Contracts\WhatsappOtpSender;
use App\Services\LogWhatsappOtpSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsappOtpSender::class, LogWhatsappOtpSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
