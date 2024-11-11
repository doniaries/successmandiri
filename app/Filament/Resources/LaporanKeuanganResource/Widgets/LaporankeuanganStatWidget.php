<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Widgets;

use App\Models\TransaksiDo;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class LaporankeuanganStatWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $today = now()->today();

        // Get saldo from perusahaan
        $saldoPerusahaan = Perusahaan::first()?->saldo ?? 0;

        // Kalkulasi transaksi hari ini
        $transaksiHariIni = TransaksiDo::whereDate('created_at', $today)
            ->select(DB::raw('
                COUNT(*) as jumlah_transaksi,
                SUM(bayar_hutang + biaya_lain + upah_bongkar) as total_pemasukan,
                SUM(sisa_bayar) as total_pengeluaran,
                SUM(tonase) as total_tonase
            '))
            ->first();

        // Data chart untuk trend pemasukan 7 hari terakhir
        $trendPemasukan = $this->hitungTrendTransaksi('pemasukan');

        // Data chart untuk trend pengeluaran 7 hari terakhir
        $trendPengeluaran = $this->hitungTrendTransaksi('pengeluaran');

        // Data chart untuk tonase 7 hari terakhir
        $chartTonase = TransaksiDo::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(tonase) as total_tonase'))
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total_tonase')
            ->map(fn($value) => $value ?? 0)
            ->toArray();

        return [
            Stat::make('Saldo Perusahaan', 'Rp ' . number_format($saldoPerusahaan, 0, ',', '.'))
                ->description('Total saldo tersedia')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Uang Masuk', 'Rp ' . number_format($transaksiHariIni->total_pemasukan ?? 0, 0, ',', '.'))
                ->description('Hutang + Biaya Lain + Upah Bongkar')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($trendPemasukan)
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ]),

            Stat::make('Total Uang Keluar', 'Rp ' . number_format($transaksiHariIni->total_pengeluaran ?? 0, 0, ',', '.'))
                ->description('Total Sisa Bayar')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart($trendPengeluaran)
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ]),

            Stat::make('Total Sawit Masuk', number_format($transaksiHariIni->total_tonase ?? 0, 0, ',', '.') . ' Kg')
                ->description('Total ' . ($transaksiHariIni->jumlah_transaksi ?? 0) . ' Transaksi')
                ->descriptionIcon('heroicon-m-scale')
                ->color('success')
                ->chart($chartTonase)
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ]),
        ];
    }

    private function hitungTrendTransaksi(string $tipe): array
    {
        $data = TransaksiDo::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw(
                $tipe === 'pemasukan'
                    ? 'SUM(bayar_hutang + biaya_lain + upah_bongkar) as total'
                    : 'SUM(sisa_bayar) as total'
            )
        )
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total')
            ->map(fn($value) => $value ?? 0)
            ->toArray();

        // Pastikan array memiliki 7 elemen, isi 0 jika tidak ada data
        $fullData = array_pad($data, 7, 0);

        return array_slice($fullData, -7); // Ambil 7 data terakhir
    }
}
