<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Observers\TicketObserver;
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
        // Keyed by the authenticated ApiClient, not the caller's IP — a
        // client behind a shared/proxied IP must not be throttled by
        // another client's traffic, and a client rotating IPs must not
        // escape its own limit.
        RateLimiter::for('api-tickets', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });

        Ticket::observe(TicketObserver::class);

        // One limiter per AI provider (6.md) — keeps a slow/rate-limited
        // provider from blocking queue workers for the others, and caps
        // spend velocity independent of the per-team budget check.
        foreach (\App\Models\AiSetting::PROVIDERS as $provider) {
            RateLimiter::for("ai-{$provider}", fn () => Limit::perMinute(20));
        }
    }
}
