<?php
// app/Filament/Resources/KeuanganResource/Widgets/KeuanganStatsWidget.php
namespace App\Filament\Resources\KeuanganResource\Widgets;

use App\Models\Keuangan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KeuanganStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $today = Carbon::today();

        // Get monthly stats
        $monthlyPemasukan = Keuangan::where('jenis', 'pemasukan')
            ->whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->sum('nominal');

        $monthlyPengeluaran = Keuangan::where('jenis', 'pengeluaran')
            ->whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->sum('nominal');

        $saldoBulanIni = $monthlyPemasukan - $monthlyPengeluaran;

        return [
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyPemasukan, 0, ',', '.'))
                ->description('Total pemasukan bulan ' . $today->format('F'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyPengeluaran, 0, ',', '.'))
                ->description('Total pengeluaran bulan ' . $today->format('F'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('danger'),

            Stat::make('Saldo Bulan Ini', 'Rp ' . number_format($saldoBulanIni, 0, ',', '.'))
                ->description('Per ' . $today->format('d F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([15, 8, 12, 9, 13, 10, 15])
                ->color($saldoBulanIni >= 0 ? 'success' : 'danger'),
        ];
    }
}
