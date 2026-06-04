<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
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
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        RateLimiter::for('web-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('login'))),
            ];
        });

        RateLimiter::for('mobile-login', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip().'|'.strtoupper((string) $request->input('access_code')).'|'.strtoupper((string) $request->input('nis'))),
            ];
        });

        RateLimiter::for('silap-sync', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('mobile-exam', function (Request $request) {
            $userKey = optional($request->user())->getAuthIdentifier() ?: $request->ip();

            return [
                Limit::perMinute(120)->by($userKey),
            ];
        });
    }
}
