<?php

namespace App\Providers;

use URL;
use Illuminate\Support\ServiceProvider;
use App\Models\{Operasional, TransaksiDo, LaporanKeuangan};
use App\Observers\{OperasionalObserver, TransaksiDoObserver, LaporanKeuanganObserver};

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

        // Register observers dengan namespace yang benar
        Operasional::observe(OperasionalObserver::class);
        TransaksiDo::observe(TransaksiDoObserver::class);
        LaporanKeuangan::observe(LaporanKeuanganObserver::class);
    }
}
