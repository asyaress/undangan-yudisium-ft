<?php

namespace App\Providers;

use App\Http\View\Composers\AdminLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('checkin-public', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Paginator::useBootstrapFour();

        View::composer([
            'layouts.dashboard',
            'admin.*',
            'monitoring.*',
            'checkin.*',
        ], AdminLayoutComposer::class);
    }
}
