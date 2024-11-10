<?php

namespace App\Providers;

use URL;
use App\Models\Operasional;
use App\Observers\OperasionalObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\TransaksiDo;
use App\Observers\TransaksiDoObserver;

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
        // if (config('app.env') === 'local') {
        //     URL::forceScheme('https');
        // }

        \Log::info('Registering Observers');
        Operasional::observe(OperasionalObserver::class);
        TransaksiDo::observe(TransaksiDoObserver::class);
    }
}
