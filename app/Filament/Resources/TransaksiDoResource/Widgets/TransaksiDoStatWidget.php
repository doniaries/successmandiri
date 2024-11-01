<?php

namespace App\Filament\Resources\TransaksiDoResource\Widgets;

use App\Models\TransaksiDo;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TransaksiDoStatWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s'; //refresh setiap 15 detik

    protected function getStats(): array
    {
        $today = now()->today();
        $startOfMonth = now()->startOfMonth();

        // Kalkulasi Pemasukan Hari Ini
        $pemasukanHariIni = TransaksiDo::whereDate('created_at', $today)
            ->select(DB::raw('
                COUNT(*) as jumlah_transaksi,
                SUM(total) as total_do,
                SUM(total - sisa_bayar) as total_pemasukan,
                SUM(tonase) as total_tonase
            '))
            ->first();

        // Data chart untuk 7 hari terakhir
        $chartData = TransaksiDo::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(tonase) as total_tonase'))
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total_tonase')
            ->toArray();

        // Hitung total sawit hari ini
        $totalSawitHariIni = TransaksiDo::whereDate('created_at', Carbon::today())
            ->sum('tonase');

        // Kalkulasi Pemasukan Bulan Ini
        $pemasukanBulanIni = TransaksiDo::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->select(DB::raw('
                COUNT(*) as jumlah_transaksi,
                SUM(total) as total_do,
                SUM(total - sisa_bayar) as total_pemasukan,
                SUM(tonase) as total_tonase
            '))
            ->first();

        return [
            Stat::make('Pemasukan Hari Ini', 'Rp ' . number_format($pemasukanHariIni->total_pemasukan ?? 0, 0, ',', '.'))
                ->description('Total ' . ($pemasukanHariIni->jumlah_transaksi ?? 0) . ' DO, ' . number_format($pemasukanHariIni->total_tonase ?? 0, 0, ',', '.') . ' Kg')
                ->icon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Nilai DO Hari Ini', 'Rp ' . number_format($pemasukanHariIni->total_do ?? 0, 0, ',', '.'))
                ->description('Total transaksi DO hari ini')
                ->icon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($pemasukanBulanIni->total_pemasukan ?? 0, 0, ',', '.'))
                ->description('Total ' . ($pemasukanBulanIni->jumlah_transaksi ?? 0) . ' DO, ' . number_format($pemasukanBulanIni->total_tonase ?? 0, 0, ',', '.') . ' Kg')
                ->icon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Total Sawit Masuk Hari Ini', number_format($totalSawitHariIni, 0, ',', '.') . ' Kg')
                ->description('Tanggal ' . Carbon::today()->format('d F Y'))
                ->descriptionIcon('heroicon-m-scale')
                ->chart($chartData)
                ->chartColor('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ]),
        ];
    }
}
