<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\LaporanKeuangan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;

class LaporanKeuanganDoStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pemasukan DO (Hari Ini)', function () {
                return 'Rp ' . number_format($this->getTotalPemasukanHariIni(), 0, ',', '.');
            })
                ->description('Upah Bongkar + Biaya Lain + Bayar Hutang')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pembayaran DO (Hari Ini)', function () {
                return 'Rp ' . number_format($this->getTotalPembayaranHariIni(), 0, ',', '.');
            })
                ->description('Pembayaran Sisa DO ke Penjual')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Saldo Terkini', function () {
                return 'Rp ' . number_format($this->getSaldoTerkini(), 0, ',', '.');
            })
                ->description('Saldo Perusahaan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }

    private function getTotalPemasukanHariIni(): int
    {
        return LaporanKeuangan::where('tipe_transaksi', 'transaksi_do')
            ->whereIn('kategori_do', ['upah_bongkar', 'biaya_lain', 'bayar_hutang'])
            ->whereDate('tanggal', Carbon::today())
            ->sum('nominal');
    }

    private function getTotalPembayaranHariIni(): int
    {
        return LaporanKeuangan::where('tipe_transaksi', 'transaksi_do')
            ->where('kategori_do', 'pembayaran_do')
            ->whereDate('tanggal', Carbon::today())
            ->sum('nominal');
    }

    private function getSaldoTerkini(): int
    {
        return Perusahaan::first()?->saldo ?? 0;
    }
}
