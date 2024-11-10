<?php

namespace App\Filament\Resources\KeuanganResource\Widgets;

use App\Models\Keuangan;
use App\Models\Perusahaan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KeuanganStatsWidget extends BaseWidget
{
    // Set polling interval ke 5 detik
    protected static ?string $pollingInterval = '5s';

    // Aktifkan polling hanya di halaman yang sedang dilihat
    public bool $readyToLoad = false;

    public function loadData()
    {
        $this->readyToLoad = true;
    }

    protected function getStats(): array
    {
        // Get saldo from perusahaan
        $saldoPerusahaan = Perusahaan::first()?->saldo ?? 0;

        // Calculate total uang masuk
        $totalMasuk = Keuangan::where('jenis_transaksi', 'Masuk')
            ->sum('jumlah');

        // Calculate total uang keluar
        $totalKeluar = Keuangan::where('jenis_transaksi', 'Keluar')
            ->sum('jumlah');

        return [
            Stat::make('Saldo Perusahaan', 'Rp ' . number_format($saldoPerusahaan, 0, ',', '.'))
                ->description('Total saldo tersedia')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([1, 1, 1, 1, 1, 1, 1]) // Garis lurus untuk menunjukkan stabilitas
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:init' => 'loadData'
                ]),

            Stat::make('Total Uang Masuk', 'Rp ' . number_format($totalMasuk, 0, ',', '.'))
                ->description('Total semua pemasukan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Total Uang Keluar', 'Rp ' . number_format($totalKeluar, 0, ',', '.'))
                ->description('Total semua pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([3, 12, 5, 8, 4, 8, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }
}
