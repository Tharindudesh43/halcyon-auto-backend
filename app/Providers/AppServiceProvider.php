<?php

namespace App\Providers;

use App\Support\SpamGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin login: limited per email (so rotating IPs doesn't help a password guesser) and per IP.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login-email:'.strtolower((string) $request->input('email'))),
            Limit::perHour(30)->by('login-email-h:'.strtolower((string) $request->input('email'))),
            Limit::perMinute(20)->by('login-ip:'.SpamGuard::clientIp($request)),
        ]);

        // Contact form: a handful of messages per visitor.
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by('contact:'.SpamGuard::clientIp($request)),
            Limit::perDay(15)->by('contact-day:'.SpamGuard::clientIp($request)),
        ]);

        // Enquiries: test-drive bookings are tight, button taps are looser.
        RateLimiter::for('enquiries', fn (Request $request) => $request->input('type') === 'test_drive'
            ? Limit::perMinutes(10, 3)->by('td:'.SpamGuard::clientIp($request))
            : Limit::perMinute(30)->by('tap:'.SpamGuard::clientIp($request)));
    }
}
