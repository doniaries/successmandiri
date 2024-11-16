<?php

namespace App\Filament\Widgets;

use App\Models\{Penjual, Perusahaan, TransaksiDo};
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\{DB, Cache};
use Carbon\Carbon;

class TransaksiWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    // Cache configuration
    protected const CACHE_KEY = 'transaksi_widget_stats';
    protected const CACHE_TTL = 300; // 5 minutes

    protected function getStats(): array
    {
        // Check jika user adalah superadmin
        if (auth()->user()->isSuperAdmin()) {
            return $this->getSuperAdminStats();
        }

        return $this->getPerusahaanStats();
    }

    protected function getSuperAdminStats(): array
    {
        $cacheKey = self::CACHE_KEY . '_superadmin';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $allPerusahaan = Perusahaan::count();
            $totalTransaksi = TransaksiDo::count();
            $totalTonase = TransaksiDo::sum('tonase');
            $totalNilaiTransaksi = TransaksiDo::sum('total');

            // Monthly trends
            $monthlyTrend = TransaksiDo::select(
                DB::raw('MONTH(tanggal) as month'),
                DB::raw('COUNT(*) as total')
            )
                ->whereYear('tanggal', date('Y'))
                ->groupBy('month')
                ->pluck('total')
                ->toArray();

            return [
                Stat::make('Total Perusahaan', number_format($allPerusahaan))
                    ->description('Semua Perusahaan Aktif')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->chart($monthlyTrend)
                    ->color('success'),

                Stat::make('Total Transaksi', number_format($totalTransaksi))
                    ->description('Seluruh Transaksi')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->chart($monthlyTrend)
                    ->color('primary'),

                Stat::make('Total Sawit', number_format($totalTonase, 2) . ' Kg')
                    ->description('Total Sawit Masuk')
                    ->descriptionIcon('heroicon-m-scale')
                    ->chart($monthlyTrend)
                    ->color('warning'),

                Stat::make('Total Nilai', 'Rp ' . number_format($totalNilaiTransaksi))
                    ->description('Total Nilai Transaksi')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->chart($monthlyTrend)
                    ->color('success'),
            ];
        });
    }

    protected function getPerusahaanStats(): array
    {
        $perusahaanId = auth()->user()->perusahaan_id;
        $cacheKey = self::CACHE_KEY . "_perusahaan_{$perusahaanId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perusahaanId) {
            $perusahaan = Perusahaan::find($perusahaanId);
            $monthlyStats = $this->getMonthlyStats($perusahaanId);
            $dailyStats = $this->getDailyStats($perusahaanId);

            return [
                Stat::make('Saldo Perusahaan', 'Rp ' . number_format($perusahaan->saldo))
                    ->description('Update Realtime')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success')
                    ->chart($monthlyStats['trend']),

                Stat::make('Transaksi Bulan Ini', number_format($monthlyStats['total_transaksi']))
                    ->description(Carbon::now()->format('F Y'))
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary')
                    ->chart($monthlyStats['trend']),

                Stat::make('Sawit Masuk', number_format($monthlyStats['total_tonase'], 2) . ' Kg')
                    ->description('Total Bulan Ini')
                    ->descriptionIcon('heroicon-m-scale')
                    ->color('warning')
                    ->chart($monthlyStats['trend']),

                Stat::make('Transaksi Hari Ini', number_format($dailyStats['total_transaksi']))
                    ->description('Rp ' . number_format($dailyStats['total_nilai']))
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('success')
                    ->chart($dailyStats['hourly_trend']),
            ];
        });
    }

    protected function getMonthlyStats(int $perusahaanId): array
    {
        $currentMonth = Carbon::now();

        $stats = TransaksiDo::query()
            ->where('perusahaan_id', $perusahaanId)
            ->whereYear('tanggal', $currentMonth->year)
            ->whereMonth('tanggal', $currentMonth->month)
            ->select([
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('SUM(total) as total_nilai')
            ])
            ->first();

        // Get monthly trend
        $trend = TransaksiDo::query()
            ->where('perusahaan_id', $perusahaanId)
            ->whereYear('tanggal', $currentMonth->year)
            ->whereMonth('tanggal', $currentMonth->month)
            ->select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();

        return [
            'total_transaksi' => $stats->total_transaksi ?? 0,
            'total_tonase' => $stats->total_tonase ?? 0,
            'total_nilai' => $stats->total_nilai ?? 0,
            'trend' => $trend,
        ];
    }

    protected function getDailyStats(int $perusahaanId): array
    {
        $stats = TransaksiDo::query()
            ->where('perusahaan_id', $perusahaanId)
            ->whereDate('tanggal', Carbon::today())
            ->select([
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('SUM(total) as total_nilai')
            ])
            ->first();

        // Get hourly trend for today
        $hourlyTrend = TransaksiDo::query()
            ->where('perusahaan_id', $perusahaanId)
            ->whereDate('tanggal', Carbon::today())
            ->select(
                DB::raw('HOUR(tanggal) as hour'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total')
            ->toArray();

        return [
            'total_transaksi' => $stats->total_transaksi ?? 0,
            'total_tonase' => $stats->total_tonase ?? 0,
            'total_nilai' => $stats->total_nilai ?? 0,
            'hourly_trend' => $hourlyTrend,
        ];
    }
}
