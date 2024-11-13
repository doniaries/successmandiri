<?php

namespace App\Filament\Resources\PenjualResource\Widgets;

use App\Models\Penjual;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PenjualStatsOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        // Query builder untuk penjual
        $query = Penjual::query();

        // Hitung statistik
        $totalPenjual = $query->count();
        $totalHutang = $query->sum('hutang');
        $penjualDenganHutang = $query->where('hutang', '>', 0)->count();
        $rataHutang = $penjualDenganHutang > 0 ? ($totalHutang / $penjualDenganHutang) : 0;

        // Hitung persentase perubahan (sebagai contoh)
        $pertumbuhanPenjual = $totalPenjual > 0 ? (($totalPenjual - 100) / 100) * 100 : 0;
        $pertumbuhanHutang = $totalHutang > 0 ? (($totalHutang - 1000000) / 1000000) * 100 : 0;

        return [
            Stat::make('Total Penjual', number_format($totalPenjual))
                ->description($pertumbuhanPenjual >= 0 ? 'Meningkat ' . number_format($pertumbuhanPenjual, 1) . '%' : 'Menurun ' . number_format(abs($pertumbuhanPenjual), 1) . '%')
                ->descriptionIcon($pertumbuhanPenjual >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([7, 4, 6, 8, $totalPenjual])
                ->color($pertumbuhanPenjual >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-users'),

            Stat::make('Total Hutang', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description($penjualDenganHutang . ' penjual dengan hutang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([4, 3, 6, 2, $totalHutang / 1000000])
                ->color($totalHutang > 10000000 ? 'danger' : 'warning')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Rata-rata Hutang', 'Rp ' . number_format($rataHutang, 0, ',', '.'))
                ->description('Per penjual dengan hutang')
                ->descriptionIcon('heroicon-m-calculator')
                ->chart([2, 4, 6, 5, 3, $rataHutang / 1000000])
                ->color($rataHutang > 5000000 ? 'danger' : 'warning')
                ->icon('heroicon-o-calculator'),
        ];
    }
}