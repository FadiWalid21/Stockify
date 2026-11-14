<?php

namespace App\Providers;

use App\Domains\Company\Contracts\TenantContextInterface;
use App\Domains\Company\Services\TenantContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
        TenantContextInterface::class,
        TenantContext::class
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('password-reset-request', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->ip() . '|' . $request->input('email')
            );
            });

        RateLimiter::for('password-reset-verify', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip() . '|' . $request->input('email')
            );
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip() . '|' . $request->input('email')
            );
        });
        }
}
