<?php

namespace App\Filament\Widgets;

use App\Models\Penjual;
use App\Models\Perusahaan;
use App\Models\TransaksiDo;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransaksiWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Hitung total sawit bulan ini
        $totalSawitBulanIni = TransaksiDo::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('tonase');

        // Hitung total transaksi bulan ini
        $totalTransaksiBulanIni = TransaksiDo::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Data chart untuk 7 hari terakhir
        $chartData = TransaksiDo::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(tonase) as total_tonase'))
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total_tonase')
            ->toArray();

        // Pastikan array memiliki 7 elemen, isi 0 jika tidak ada data
        $chartData = array_pad($chartData, 7, 0);

        // Data chart untuk trend bulanan (3 bulan terakhir)
        $monthlyChartData = TransaksiDo::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(tonase) as total_tonase')
        )
            ->whereBetween('created_at', [Carbon::now()->subMonths(3), Carbon::now()])
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('total_tonase')
            ->toArray();

        return [
            Stat::make('Jumlah Penjual', Penjual::count())
                ->description('Total penjual terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->chartColor('success'),

            Stat::make('Jumlah Perusahaan', Perusahaan::count())
                ->description('Total perusahaan terdaftar')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning')
                ->chart([5, 12, 7, 9, 14, 3, 8])
                ->chartColor('warning'),

            Stat::make('Total Transaksi Bulan Ini', $totalTransaksiBulanIni)
                ->description('Periode ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->chart([8, 15, 4, 12, 9, 16, 5])
                ->chartColor('primary'),

            Stat::make('Total Sawit Masuk Bulan Ini', number_format($totalSawitBulanIni, 0, ',', '.') . ' Kg')
                ->description('Periode ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-scale')
                ->chart($monthlyChartData)
                ->chartColor('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg rounded-lg',
                    'style' => 'transition-duration: 500ms;'
                ])
                ->color('success'),

            Stat::make('Total Sawit Masuk Hari Ini', number_format(TransaksiDo::whereDate('created_at', Carbon::today())->sum('tonase'), 0, ',', '.') . ' Kg')
                ->description('Tanggal ' . Carbon::today()->format('d F Y'))
                ->descriptionIcon('heroicon-m-scale')
                ->chart($chartData)
                ->chartColor('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ])
                ->color('success'),
        ];
    }

    // Auto refresh setiap 15 detik
    protected static ?string $pollingInterval = '15s';

    // Konfigurasi tampilan chart
    protected function getChartOptions(): array
    {
        return [
            'chart' => [
                'type' => 'line',
                'toolbar' => [
                    'show' => false,
                ],
                'animations' => [
                    'enabled' => true,
                    'easing' => 'easeinout',
                    'speed' => 800,
                    'animateGradually' => [
                        'enabled' => true,
                        'delay' => 150
                    ],
                    'dynamicAnimation' => [
                        'enabled' => true,
                        'speed' => 350
                    ]
                ],
            ],
            'grid' => [
                'show' => false,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'tooltip' => [
                'theme' => 'dark',
                'shared' => true,
            ],
        ];
    }

    public static function canView(): bool
    {
        return true;
    }

    public function mount()
    {
        $penjual = Penjual::find(1); // Ganti 1 dengan ID yang sesuai
        $this->paymentHistory = $penjual->paymentHistory; // Panggilan yang benar
    }
}
