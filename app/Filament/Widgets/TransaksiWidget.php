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
    // Polling interval yang lebih efisien
    protected static ?string $pollingInterval = '30s';

    // Cache key untuk statistik
    protected const CACHE_KEY = 'transaksi_widget_stats';
    protected const CACHE_TTL = 300; // 5 menit

    // Lazy load widget
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            // Query optimization dengan select spesifik
            $monthlyStats = $this->getMonthlyStats();
            $dailyStats = $this->getDailyStats();
            $overallStats = $this->getOverallStats();

            return [
                // Statistik Penjual & Saldo
                $this->createPenjualStat($overallStats),

                // Statistik Transaksi Bulanan
                $this->createMonthlyStat($monthlyStats),

                // Statistik Transaksi Harian
                $this->createDailyStat($dailyStats),

                // Statistik Sawit
                $this->createSawitStat($monthlyStats, $dailyStats)
            ];
        });
    }

    protected function getMonthlyStats(): array
    {
        $currentMonth = Carbon::now();

        return TransaksiDo::query()
            ->select([
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('SUM(total) as total_nilai'),
                DB::raw('AVG(tonase) as avg_tonase')
            ])
            ->whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->first()
            ->toArray();
    }

    protected function getDailyStats(): array
    {
        return TransaksiDo::query()
            ->select([
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('SUM(total) as total_nilai')
            ])
            ->whereDate('tanggal', Carbon::today())
            ->first()
            ->toArray();
    }

    protected function getOverallStats(): array
    {
        return [
            'total_penjual' => Penjual::count(),
            'saldo_perusahaan' => Perusahaan::value('saldo'),
        ];
    }

    protected function createPenjualStat(array $stats): Stat
    {
        return Stat::make('Total Penjual', $stats['total_penjual'])
            ->description('Penjual Aktif')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('success')
            ->chart($this->getChartData('penjual'))
            ->chartColor('success')
            ->defer();
    }

    protected function createMonthlyStat(array $stats): Stat
    {
        return Stat::make(
            'Transaksi Bulan Ini',
            number_format($stats['total_transaksi'])
        )
            ->description(Carbon::now()->format('F Y'))
            ->descriptionIcon('heroicon-m-document-text')
            ->color('primary')
            ->chart($this->getChartData('monthly'))
            ->chartColor('primary')
            ->defer();
    }

    protected function createDailyStat(array $stats): Stat
    {
        return Stat::make(
            'Transaksi Hari Ini',
            number_format($stats['total_transaksi'])
        )
            ->description(Carbon::today()->format('d F Y'))
            ->descriptionIcon('heroicon-m-document-text')
            ->color('warning')
            ->chart($this->getChartData('daily'))
            ->chartColor('warning')
            ->defer();
    }

    protected function createSawitStat(array $monthlyStats, array $dailyStats): Stat
    {
        return Stat::make('Total Sawit', function () use ($monthlyStats, $dailyStats) {
            return [
                'bulan' => number_format($monthlyStats['total_tonase']) . ' Kg',
                'hari' => number_format($dailyStats['total_tonase']) . ' Kg'
            ];
        })
            ->description('Total Sawit Masuk')
            ->descriptionIcon('heroicon-m-scale')
            ->color('success')
            ->chart($this->getChartData('sawit'))
            ->chartColor('success')
            ->defer();
    }

    protected function getChartData(string $type): array
    {
        // Implementasi chart data sesuai tipe
        return Cache::remember(
            "chart_data_{$type}",
            300,
            fn() => $this->generateChartData($type)
        );
    }

    protected function generateChartData(string $type): array
    {
        // Pattern matching untuk generate data chart
        return match ($type) {
            'penjual' => [7, 2, 10, 3, 15, 4, 17],
            'monthly' => [8, 15, 4, 12, 9, 16, 5],
            'daily'   => [5, 12, 7, 9, 14, 3, 8],
            'sawit'   => [10, 8, 15, 12, 9, 11, 13],
            default   => [0, 0, 0, 0, 0, 0, 0]
        };
    }

    // Konfigurasi chart yang optimal
    protected function getChartOptions(): array
    {
        return [
            'chart' => [
                'type' => 'line',
                'height' => 50,
                'sparkline' => [
                    'enabled' => true,
                ],
                'animations' => [
                    'enabled' => false,
                ],
            ],
            'stroke' => [
                'width' => 2,
                'curve' => 'smooth',
            ],
            'tooltip' => [
                'enabled' => false,
            ],
        ];
    }
}
