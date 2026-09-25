<?php

namespace App\Providers;

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
        // Define the 'api' rate limiter
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \App\Models\Order::observe(\App\Observers\OrderObserver::class);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                $emailName = \App\Models\SiteSetting::get('email_from_name');
                if (!empty($emailName)) {
                    config(['mail.from.name' => $emailName]);
                }
            }
        } catch (\Exception $e) {
            // Ignore during migrations or initial setup
        }
    }
}
