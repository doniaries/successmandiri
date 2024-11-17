<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\{LaporanKeuangan, Team, TransaksiDo};
use Illuminate\Support\Facades\DB;

class LaporanKeuanganDoStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Ringkasan Transaksi DO';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // Hitung data hari ini
        $pemasukanHariIni = $this->getPemasukanHariIni();
        $pembayaranHariIni = $this->getPembayaranHariIni();

        // Hitung data bulan ini
        $pemasukanBulanIni = $this->getPemasukanBulanIni();
        $pembayaranBulanIni = $this->getPembayaranBulanIni();

        return [
            // Statistik Pemasukan
            Stat::make('Pemasukan DO Hari Ini', fn() => 'Rp ' . number_format($pemasukanHariIni['total'], 0, ',', '.'))
                ->description(sprintf(
                    "Upah Bongkar: Rp %s\nBiaya Lain: Rp %s\nBayar Hutang: Rp %s",
                    number_format($pemasukanHariIni['upah_bongkar'], 0, ',', '.'),
                    number_format($pemasukanHariIni['biaya_lain'], 0, ',', '.'),
                    number_format($pemasukanHariIni['bayar_hutang'], 0, ',', '.')
                ))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // Statistik Pembayaran
            Stat::make('Pembayaran DO Hari Ini', fn() => 'Rp ' . number_format($pembayaranHariIni['total'], 0, ',', '.'))
                ->description(sprintf(
                    "Tunai: Rp %s\nTransfer: Rp %s\nTotal DO: %d transaksi",
                    number_format($pembayaranHariIni['tunai'], 0, ',', '.'),
                    number_format($pembayaranHariIni['transfer'], 0, ',', '.'),
                    $pembayaranHariIni['jumlah_do']
                ))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // Statistik Saldo dan Rata-rata
            Stat::make('Informasi Keuangan', fn() => 'Rp ' . number_format($this->getSaldoTerkini(), 0, ',', '.'))
                ->description(sprintf(
                    "Rata² Pemasukan/Hari: Rp %s\nRata² Pembayaran/Hari: Rp %s\nTotal DO Bulan Ini: %d DO",
                    number_format($pemasukanBulanIni['rata_rata'], 0, ',', '.'),
                    number_format($pembayaranBulanIni['rata_rata'], 0, ',', '.'),
                    $pembayaranBulanIni['jumlah_do']
                ))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }

    private function getPemasukanHariIni(): array
    {
        $data = LaporanKeuangan::where('kategori', 'DO')
            ->where('jenis_transaksi', 'Pemasukan')
            ->whereDate('tanggal', Carbon::today())
            ->select('sub_kategori', DB::raw('SUM(nominal) as total'))
            ->groupBy('sub_kategori')
            ->get()
            ->pluck('total', 'sub_kategori')
            ->toArray();

        return [
            'upah_bongkar' => $data['Upah Bongkar'] ?? 0,
            'biaya_lain' => $data['Biaya Lain'] ?? 0,
            'bayar_hutang' => $data['Bayar Hutang'] ?? 0,
            'total' => array_sum($data)
        ];
    }

    private function getPembayaranHariIni(): array
    {
        $data = LaporanKeuangan::where('kategori', 'DO')
            ->where('jenis_transaksi', 'Pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->select(
                'cara_pembayaran',
                DB::raw('COUNT(DISTINCT referensi_id) as jumlah_do'),
                DB::raw('SUM(nominal) as total')
            )
            ->groupBy('cara_pembayaran')
            ->get();

        return [
            'tunai' => $data->where('cara_pembayaran', 'Tunai')->first()?->total ?? 0,
            'transfer' => $data->where('cara_pembayaran', 'Transfer')->first()?->total ?? 0,
            'jumlah_do' => $data->sum('jumlah_do'),
            'total' => $data->sum('total')
        ];
    }

    private function getPemasukanBulanIni(): array
    {
        $data = LaporanKeuangan::where('kategori', 'DO')
            ->where('jenis_transaksi', 'Pemasukan')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->select(
                DB::raw('SUM(nominal) as total'),
                DB::raw('COUNT(DISTINCT DATE(tanggal)) as jumlah_hari')
            )
            ->first();

        $jumlahHari = $data->jumlah_hari ?? 1;
        $total = $data->total ?? 0;

        return [
            'total' => $total,
            'jumlah_hari' => $jumlahHari,
            'rata_rata' => $jumlahHari > 0 ? ($total / $jumlahHari) : 0
        ];
    }

    private function getPembayaranBulanIni(): array
    {
        $data = LaporanKeuangan::where('kategori', 'DO')
            ->where('jenis_transaksi', 'Pengeluaran')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->select(
                DB::raw('SUM(nominal) as total'),
                DB::raw('COUNT(DISTINCT referensi_id) as jumlah_do'),
                DB::raw('COUNT(DISTINCT DATE(tanggal)) as jumlah_hari')
            )
            ->first();

        $jumlahHari = $data->jumlah_hari ?? 1;
        $total = $data->total ?? 0;

        return [
            'total' => $total,
            'jumlah_do' => $data->jumlah_do ?? 0,
            'jumlah_hari' => $jumlahHari,
            'rata_rata' => $jumlahHari > 0 ? ($total / $jumlahHari) : 0
        ];
    }

    private function getSaldoTerkini(): int
    {
        return Team::first()?->saldo ?? 0;
    }
}
