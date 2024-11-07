<?php

namespace App\Filament\Resources\TransaksiDoResource\Widgets;

use App\Models\TransaksiDo;
use App\Models\Operasional;
use Illuminate\Support\Facades\DB;
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

        // Hitung saldo perusahaan hari ini
        $saldoHariIni = $this->hitungSaldoHariIni();
        $saldoKemarin = $this->hitungSaldoKemarin();
        $perubahanSaldo = $saldoKemarin != 0 ?
            (($saldoHariIni - $saldoKemarin) / $saldoKemarin) * 100 :
            0;

        // Data chart untuk trend saldo 7 hari terakhir
        $trendSaldo = $this->hitungTrendSaldo();

        return [
            // Stat Saldo Perusahaan (Ditambahkan di awal)
            Stat::make('Saldo Perusahaan', 'Rp ' . number_format($saldoHariIni, 0, ',', '.'))
                ->description($perubahanSaldo >= 0 ?
                    'Naik ' . number_format(abs($perubahanSaldo), 1) . '% dari kemarin' :
                    'Turun ' . number_format(abs($perubahanSaldo), 1) . '% dari kemarin')
                ->descriptionIcon($perubahanSaldo >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($perubahanSaldo >= 0 ? 'success' : 'danger')
                ->chart($trendSaldo)
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105 hover:shadow-lg',
                ]),

            // Stat yang sudah ada sebelumnya
            Stat::make('Pemasukan Hari Ini', 'Rp ' . number_format($pemasukanHariIni->total_pemasukan ?? 0, 0, ',', '.'))
                ->description('Total ' . ($pemasukanHariIni->jumlah_transaksi ?? 0) . ' DO, ' . number_format($pemasukanHariIni->total_tonase ?? 0, 0, ',', '.') . ' Kg')
                ->icon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Nilai DO Hari Ini', 'Rp ' . number_format($pemasukanHariIni->total_do ?? 0, 0, ',', '.'))
                ->description('Total transaksi DO hari ini')
                ->icon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Total Sawit Masuk Hari Ini', number_format($pemasukanHariIni->total_tonase ?? 0, 0, ',', '.') . ' Kg')
                ->description('Tanggal ' . Carbon::today()->format('d F Y'))
                ->descriptionIcon('heroicon-m-scale')
                ->chart($chartData)
                ->chartColor('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-105',
                ]),
        ];
    }

    private function hitungSaldoHariIni(): float
    {
        $tanggal = Carbon::today();

        // Pemasukan dari Transaksi DO
        $pemasukan = TransaksiDo::whereDate('created_at', $tanggal)
            ->sum('sisa_bayar');

        // Pengeluaran dari Operasional
        $pengeluaran = Operasional::whereDate('created_at', $tanggal)
            ->where('operasional', '!=', 'isi_saldo')
            ->sum('nominal');

        // Tambahan saldo dari isi_saldo
        $isiSaldo = Operasional::whereDate('created_at', $tanggal)
            ->where('operasional', 'isi_saldo')
            ->sum('nominal');

        return $pemasukan - $pengeluaran + $isiSaldo;
    }

    private function hitungSaldoKemarin(): float
    {
        $tanggal = Carbon::yesterday();

        $pemasukan = TransaksiDo::whereDate('created_at', $tanggal)
            ->sum('sisa_bayar');

        $pengeluaran = Operasional::whereDate('created_at', $tanggal)
            ->where('operasional', '!=', 'isi_saldo')
            ->sum('nominal');

        $isiSaldo = Operasional::whereDate('created_at', $tanggal)
            ->where('operasional', 'isi_saldo')
            ->sum('nominal');

        return $pemasukan - $pengeluaran + $isiSaldo;
    }

    private function hitungTrendSaldo(): array
    {
        $trendSaldo = [];

        // Hitung saldo untuk 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);

            $pemasukan = TransaksiDo::whereDate('created_at', $tanggal)
                ->sum('sisa_bayar');

            $pengeluaran = Operasional::whereDate('created_at', $tanggal)
                ->where('operasional', '!=', 'isi_saldo')
                ->sum('nominal');

            $isiSaldo = Operasional::whereDate('created_at', $tanggal)
                ->where('operasional', 'isi_saldo')
                ->sum('nominal');

            $trendSaldo[] = $pemasukan - $pengeluaran + $isiSaldo;
        }

        return $trendSaldo;
    }
}
