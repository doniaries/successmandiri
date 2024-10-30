<?php

namespace App\Filament\Widgets;

use App\Models\Pekerja;
use App\Models\Penjual;
use App\Models\Perusahaan;
use App\Models\TransaksiDo;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TransaksiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make('Jumlah Penjual', Penjual::count()),
            Stat::make('Jumlah Pekerja', Pekerja::count()),
            Stat::make('Jumlah Perusahaan', Perusahaan::count()),
            Stat::make('Total Transaksi Bulan Ini', TransaksiDo::count()),

        ];
    }
}
