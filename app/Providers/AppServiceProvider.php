<?php

namespace App\Providers;

use URL;
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

        // //---untuk perbaikan agar ngrok jalan
        if (config('app.env') === 'local') {
            URL::forceScheme('https');
        }
    }
}
