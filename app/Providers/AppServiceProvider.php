<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('access-request', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perDay(10)->by($request->ip()),
        ]);

        RateLimiter::for('secret-pin', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('public-share-view', fn (Request $request) => Limit::perMinute(120)->by($request->ip().'|'.$request->route('token'))
        );

        RateLimiter::for('public-share-download', fn (Request $request) => [
            Limit::perMinute(30)->by($request->ip().'|'.$request->route('token')),
            Limit::perHour(300)->by($request->ip()),
        ]);

        // Enforce HTTPS scheme only when actually requested via HTTPS (e.g. Cloudflare Tunnel)
        if (request()->header('x-forwarded-proto') === 'https' || str_contains((string) request()->header('cf-visitor'), 'https')) {
            URL::forceScheme('https');
        }
    }
}
