<?php

namespace App\Filament\Resources\RiwayatHutangResource\Widgets;

use App\Models\RiwayatHutang;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class RiwayatHutangStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Ambil summary data
        $summary = RiwayatHutang::select(
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(CASE WHEN jenis = "penambahan" THEN nominal ELSE 0 END) as total_penambahan'),
            DB::raw('SUM(CASE WHEN jenis = "pengurangan" THEN nominal ELSE 0 END) as total_pengurangan'),
            DB::raw('COUNT(DISTINCT entitas_id) as total_penjual')
        )->first();

        // Ambil trend 7 hari terakhir untuk penambahan
        $trendPenambahan = RiwayatHutang::where('jenis', 'penambahan')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COALESCE(SUM(nominal), 0) as total')
            ])
            ->pluck('total')
            ->toArray();

        // Ambil trend 7 hari terakhir untuk pengurangan
        $trendPengurangan = RiwayatHutang::where('jenis', 'pengurangan')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COALESCE(SUM(nominal), 0) as total')
            ])
            ->pluck('total')
            ->toArray();

        // Hitung hutang berjalan
        $hutangBerjalan = $summary->total_penambahan - $summary->total_pengurangan;

        return [
            // Total transaksi dan penjual
            Stat::make('Total Transaksi', number_format($summary->total_transaksi))
                ->description($summary->total_penjual . ' Penjual Terlibat')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),

            // Total penambahan hutang
            Stat::make('Total Penambahan Hutang', 'Rp ' . number_format($summary->total_penambahan))
                ->description('Trend 7 Hari Terakhir')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($trendPenambahan)
                ->color('danger'),

            // Total pengurangan/pembayaran hutang
            Stat::make('Total Pembayaran Hutang', 'Rp ' . number_format($summary->total_pengurangan))
                ->description('Trend 7 Hari Terakhir')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart($trendPengurangan)
                ->color('success'),

            // Total hutang berjalan
            Stat::make('Sisa Hutang Berjalan', 'Rp ' . number_format($hutangBerjalan))
                ->description(
                    number_format(
                        $summary->total_pengurangan > 0
                            ? ($summary->total_pengurangan / $summary->total_penambahan * 100)
                            : 0,
                        1
                    ) . '% Terbayar'
                )
                ->descriptionIcon('heroicon-m-calculator')
                ->color($hutangBerjalan > 0 ? 'warning' : 'success'),
        ];
    }
}
