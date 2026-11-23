<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // Each enquiry sends an email to a real inbox, so throttle hard to limit spam.
        RateLimiter::for('enquiries', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
