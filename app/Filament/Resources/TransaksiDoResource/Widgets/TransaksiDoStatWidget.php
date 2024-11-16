<?php

namespace App\Filament\Resources\TransaksiDoResource\Widgets;

use App\Models\TransaksiDo;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Cache};

class TransaksiDoStatWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected const CACHE_KEY = 'transaksido_stats';
    protected const CACHE_TTL = 300; // 5 minutes

    protected function getStats(): array
    {
        $perusahaanId = auth()->user()->perusahaan_id;
        $cacheKey = self::CACHE_KEY . "_{$perusahaanId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perusahaanId) {
            $today = Carbon::today();
            $perusahaan = Perusahaan::find($perusahaanId);

            if (!$perusahaan) {
                return $this->getErrorStats('Data perusahaan tidak ditemukan');
            }

            $transaksiHariIni = $this->getTransaksiHariIni($perusahaanId);

            return [
                // Saldo Stats
                Stat::make('Saldo Saat Ini', 'Rp ' . number_format($perusahaan->saldo))
                    ->description('Update otomatis')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('success'),

                // Daily Transaction Stats
                Stat::make('Transaksi DO Hari Ini', "{$transaksiHariIni['total_transaksi']} Transaksi")
                    ->description("Total {$transaksiHariIni['total_tonase']} Kg")
                    ->descriptionIcon('heroicon-m-scale')
                    ->color('primary'),

                // Income Stats
                Stat::make(
                    'Total Pemasukan',
                    'Rp ' . number_format($transaksiHariIni['total_pemasukan'])
                )
                    ->description($this->getIncomeDescription($transaksiHariIni))
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                // Expense Stats
                Stat::make(
                    'Total Pengeluaran',
                    'Rp ' . number_format($transaksiHariIni['total_pengeluaran'])
                )
                    ->description($this->getExpenseDescription($transaksiHariIni))
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('danger'),
            ];
        });
    }

    protected function getTransaksiHariIni(int $perusahaanId): array
    {
        return TransaksiDo::query()
            ->where('perusahaan_id', $perusahaanId)
            ->whereDate('tanggal', Carbon::today())
            ->select([
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('COALESCE(SUM(tonase), 0) as total_tonase'),
                DB::raw('COALESCE(SUM(upah_bongkar + biaya_lain), 0) as total_pemasukan'),
                DB::raw('COALESCE(SUM(sisa_bayar), 0) as total_pengeluaran'),
                DB::raw('COALESCE(SUM(upah_bongkar), 0) as upah_bongkar'),
                DB::raw('COALESCE(SUM(biaya_lain), 0) as biaya_lain'),
                DB::raw('COALESCE(SUM(pembayaran_hutang), 0) as bayar_hutang'),
                DB::raw('COUNT(CASE WHEN cara_bayar = "Tunai" THEN 1 END) as tunai_count'),
                DB::raw('COUNT(CASE WHEN cara_bayar = "Transfer" THEN 1 END) as transfer_count'),
            ])
            ->first()
            ->toArray();
    }

    protected function getIncomeDescription(array $stats): string
    {
        $components = [];

        if ($stats['upah_bongkar'] > 0) {
            $components[] = 'Upah: Rp ' . number_format($stats['upah_bongkar']);
        }

        if ($stats['biaya_lain'] > 0) {
            $components[] = 'Biaya: Rp ' . number_format($stats['biaya_lain']);
        }

        if ($stats['bayar_hutang'] > 0) {
            $components[] = 'Hutang: Rp ' . number_format($stats['bayar_hutang']);
        }

        return empty($components)
            ? 'Belum ada pemasukan'
            : implode("\n", $components);
    }

    protected function getExpenseDescription(array $stats): string
    {
        $components = [];

        if ($stats['tunai_count'] > 0) {
            $components[] = "Tunai: {$stats['tunai_count']} DO";
        }

        if ($stats['transfer_count'] > 0) {
            $components[] = "Transfer: {$stats['transfer_count']} DO";
        }

        return empty($components)
            ? 'Belum ada pengeluaran'
            : implode("\n", $components);
    }

    protected function getErrorStats(string $message): array
    {
        return [
            Stat::make('Error', $message)
                ->description('Terjadi kesalahan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
        ];
    }
}
