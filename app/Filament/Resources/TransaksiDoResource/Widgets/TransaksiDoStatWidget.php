<?php

namespace App\Filament\Resources\TransaksiDoResource\Widgets;

use App\Models\TransaksiDo;
use App\Models\Perusahaan;
use App\Models\LaporanKeuangan;
use Illuminate\Support\Facades\{DB, Log};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TransaksiDoStatWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        try {
            // Cek data perusahaan
            $perusahaan = Perusahaan::first();
            if (!$perusahaan) {
                return [
                    Stat::make('Status', 'Data Tidak Tersedia')
                        ->description('Data perusahaan belum diatur')
                        ->descriptionIcon('heroicon-m-exclamation-triangle')
                        ->color('danger')
                ];
            }

            // Data transaksi hari ini
            $transaksiHariIni = TransaksiDo::whereDate('tanggal', Carbon::today())
                ->selectRaw('
                COUNT(*) as total_transaksi,
                COALESCE(SUM(CAST(tonase as DECIMAL)), 0) as total_tonase,
                COALESCE(SUM(CAST(sisa_bayar as DECIMAL)), 0) as total_pengeluaran,
                COALESCE(SUM(CAST(pembayaran_hutang as DECIMAL)), 0) as total_bayar_hutang,
                COALESCE(SUM(CAST(biaya_lain as DECIMAL)), 0) as total_biaya_lain,
                COALESCE(SUM(CAST(upah_bongkar as DECIMAL)), 0) as total_upah
            ')
                ->first();

            // Pastikan value di-cast ke string
            $totalTransaksi = (string)($transaksiHariIni->total_transaksi ?? 0);
            $totalTonase = (string)($transaksiHariIni->total_tonase ?? 0);
            $saldo = (string)($perusahaan->saldo ?? 0);
            $totalPengeluaran = (string)($transaksiHariIni->total_pengeluaran ?? 0);

            // Return stats yang sudah dipastikan tipe datanya string
            return [
                // Stat Saldo
                Stat::make('Saldo Saat Ini', 'Rp ' . number_format((float)$saldo, 0, ',', '.'))
                    ->description('Update otomatis')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('success'),

                // Stat Transaksi
                Stat::make('Jumlah Transaksi', "$totalTransaksi Transaksi")
                    ->description("Total $totalTonase Kg")
                    ->descriptionIcon('heroicon-m-scale')
                    ->color('primary'),

                // Stat Pemasukan
                Stat::make('Total Pemasukan Hari Ini', 'Rp ' . number_format((float)($transaksiHariIni->total_bayar_hutang + $transaksiHariIni->total_biaya_lain + $transaksiHariIni->total_upah), 0, ',', '.'))
                    ->description('Hutang + Biaya + Upah')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                // Stat Pengeluaran
                Stat::make('Total Pengeluaran Hari Ini', 'Rp ' . number_format((float)$totalPengeluaran, 0, ',', '.'))
                    ->description('Pembayaran DO')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('danger')
            ];
        } catch (\Exception $e) {
            Log::error('Error di widget stats: ' . $e->getMessage());
            // Return fallback stats jika terjadi error
            return [
                Stat::make('Status', 'Error')
                    ->description($e->getMessage())
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger')
            ];
        }
    }

    private function getTransaksiHariIni($today)
    {
        return TransaksiDo::where('tanggal', '>=', $today)
            ->selectRaw('
                COUNT(*) as jumlah_transaksi,
                COALESCE(SUM(tonase), 0) as total_tonase,
                COALESCE(SUM(pembayaran_hutang), 0) as total_bayar_hutang,
                COALESCE(SUM(biaya_lain), 0) as total_biaya_lain,
                COALESCE(SUM(upah_bongkar), 0) as total_upah_bongkar,
                COALESCE(SUM(sisa_bayar), 0) as total_sisa_bayar
            ')
            ->first();
    }

    private function getLaporanKeuanganHariIni($today)
    {
        return LaporanKeuangan::where('tanggal', '>=', $today)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN jenis = "masuk" THEN nominal ELSE 0 END), 0) as total_masuk,
                COALESCE(SUM(CASE WHEN jenis = "keluar" THEN nominal ELSE 0 END), 0) as total_keluar
            ')
            ->first();
    }

    private function getKomponenPemasukan($transaksiHariIni): string
    {
        $komponenPemasukan = [];

        if ($transaksiHariIni->total_bayar_hutang > 0) {
            $komponenPemasukan[] = 'Hutang: Rp ' . number_format($transaksiHariIni->total_bayar_hutang, 0, ',', '.');
        }

        if ($transaksiHariIni->total_biaya_lain > 0) {
            $komponenPemasukan[] = 'Biaya Lain: Rp ' . number_format($transaksiHariIni->total_biaya_lain, 0, ',', '.');
        }

        if ($transaksiHariIni->total_upah_bongkar > 0) {
            $komponenPemasukan[] = 'Upah: Rp ' . number_format($transaksiHariIni->total_upah_bongkar, 0, ',', '.');
        }

        return empty($komponenPemasukan) ?
            'Belum ada pemasukan' :
            implode(', ', $komponenPemasukan);
    }

    private function getKomponenPengeluaran($transaksiHariIni): string
    {
        if ($transaksiHariIni && $transaksiHariIni->total_sisa_bayar > 0) {
            return 'DO: Rp ' . number_format($transaksiHariIni->total_sisa_bayar, 0, ',', '.');
        }

        return 'Belum ada pengeluaran';
    }
}
