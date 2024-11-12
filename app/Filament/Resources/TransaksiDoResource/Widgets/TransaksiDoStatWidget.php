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
            $perusahaan = Perusahaan::first();
            if (!$perusahaan) {
                Log::warning('Data perusahaan tidak ditemukan');
                return [];
            }

            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            try {
                $transaksiHariIni = TransaksiDo::where('tanggal', '>=', $today)
                    ->selectRaw('
                        COUNT(*) as jumlah_transaksi,
                        COALESCE(SUM(tonase), 0) as total_tonase,
                        COALESCE(SUM(pembayaran_hutang + biaya_lain + upah_bongkar), 0) as total_pemasukan,
                        COALESCE(SUM(sisa_bayar), 0) as total_pengeluaran
                    ')
                    ->first();

                // Laporan Keuangan hari ini
                $laporanHariIni = LaporanKeuangan::where('tanggal', '>=', $today)
                    ->selectRaw('
                        COALESCE(SUM(CASE
                            WHEN jenis = "masuk" AND (
                                kategori_do IN ("pembayaran_hutang", "biaya_lain", "upah_bongkar") OR
                                kategori_operasional_id IS NOT NULL
                            )
                            THEN nominal
                            ELSE 0
                        END), 0) as total_masuk,
                        COALESCE(SUM(CASE
                            WHEN jenis = "keluar" AND (
                                kategori_do = "pembayaran_do" OR
                                kategori_operasional_id IS NOT NULL
                            )
                            THEN nominal
                            ELSE 0
                        END), 0) as total_keluar
                    ')
                    ->first();

                // Trend Charts
                $trendPemasukan = $this->hitungTrendTransaksi('pemasukan');
                $trendPengeluaran = $this->hitungTrendTransaksi('pengeluaran');
                $chartTonase = $this->hitungTrendTonase();

                // Perbandingan data
                $comparisonData = $this->hitungPerbandingan();

                // Log debugging
                Log::info('Stats Data:', [
                    'saldo' => $perusahaan->saldo,
                    'pemasukan_hari_ini' => $laporanHariIni->total_masuk,
                    'pengeluaran_hari_ini' => $laporanHariIni->total_keluar,
                    'transaksi_stats' => $transaksiHariIni->toArray()
                ]);

                return [
                    Stat::make('Saldo Saat Ini', 'Rp ' . number_format($perusahaan->saldo, 0, ',', '.'))
                        ->description('Update otomatis setiap 15 detik')
                        ->descriptionIcon('heroicon-m-arrow-path')
                        ->color('success'),

                    Stat::make('Pemasukan Hari Ini', 'Rp ' . number_format($laporanHariIni->total_masuk, 0, ',', '.'))
                        ->description($this->formatPercentageChange($comparisonData['pemasukan_change']))
                        ->descriptionIcon($comparisonData['pemasukan_change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                        ->chart($trendPemasukan)
                        ->color($comparisonData['pemasukan_change'] >= 0 ? 'success' : 'danger'),

                    Stat::make('Pengeluaran Hari Ini', 'Rp ' . number_format($laporanHariIni->total_keluar, 0, ',', '.'))
                        ->description($this->formatPercentageChange($comparisonData['pengeluaran_change']))
                        ->descriptionIcon($comparisonData['pengeluaran_change'] <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                        ->chart($trendPengeluaran)
                        ->color($comparisonData['pengeluaran_change'] <= 0 ? 'success' : 'danger'),

                    Stat::make('Total Sawit Masuk', number_format($transaksiHariIni->total_tonase, 0, ',', '.') . ' Kg')
                        ->description('Total ' . $transaksiHariIni->jumlah_transaksi . ' Transaksi')
                        ->descriptionIcon('heroicon-m-scale')
                        ->chart($chartTonase)
                        ->color('success')
                        ->extraAttributes([
                            'class' => 'cursor-pointer transition-all hover:scale-105',
                        ]),
                ];
            } catch (\Exception $e) {
                Log::error('Error mengambil data transaksi: ' . $e->getMessage());
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error di TransaksiDoStatWidget: ' . $e->getMessage());
            return [];
        }
    }

    private function hitungTrendTransaksi(string $tipe): array
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $query = LaporanKeuangan::select(
            DB::raw('DATE(tanggal) as date'),
            DB::raw(
                $tipe === 'pemasukan'
                    ? 'COALESCE(SUM(CASE WHEN jenis = "masuk" THEN nominal ELSE 0 END), 0) as total'
                    : 'COALESCE(SUM(CASE WHEN jenis = "keluar" THEN nominal ELSE 0 END), 0) as total'
            )
        )
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->orderBy('date', 'asc');

        $dates = collect(range(0, 6))->map(function ($day) use ($startDate) {
            return $startDate->copy()->addDays($day)->format('Y-m-d');
        });

        $data = $query->get()->pluck('total', 'date');

        return $dates->map(function ($date) use ($data) {
            return $data[$date] ?? 0;
        })->values()->toArray();
    }

    private function hitungTrendTonase(): array
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $query = TransaksiDo::select(
            DB::raw('DATE(tanggal) as date'),
            DB::raw('COALESCE(SUM(tonase), 0) as total_tonase')
        )
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->orderBy('date', 'asc');

        $dates = collect(range(0, 6))->map(function ($day) use ($startDate) {
            return $startDate->copy()->addDays($day)->format('Y-m-d');
        });

        $data = $query->get()->pluck('total_tonase', 'date');

        return $dates->map(function ($date) use ($data) {
            return $data[$date] ?? 0;
        })->values()->toArray();
    }

    private function hitungPerbandingan(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $dataHariIni = LaporanKeuangan::where('tanggal', '>=', $today)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN jenis = "masuk" THEN nominal ELSE 0 END), 0) as total_masuk,
                COALESCE(SUM(CASE WHEN jenis = "keluar" THEN nominal ELSE 0 END), 0) as total_keluar
            ')
            ->first();

        $dataKemarin = LaporanKeuangan::whereBetween('tanggal', [$yesterday, $today])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN jenis = "masuk" THEN nominal ELSE 0 END), 0) as total_masuk,
                COALESCE(SUM(CASE WHEN jenis = "keluar" THEN nominal ELSE 0 END), 0) as total_keluar
            ')
            ->first();

        $pemasukanChange = $dataKemarin->total_masuk > 0
            ? (($dataHariIni->total_masuk - $dataKemarin->total_masuk) / $dataKemarin->total_masuk) * 100
            : ($dataHariIni->total_masuk > 0 ? 100 : 0);

        $pengeluaranChange = $dataKemarin->total_keluar > 0
            ? (($dataHariIni->total_keluar - $dataKemarin->total_keluar) / $dataKemarin->total_keluar) * 100
            : ($dataHariIni->total_keluar > 0 ? 100 : 0);

        return [
            'pemasukan_change' => $pemasukanChange,
            'pengeluaran_change' => $pengeluaranChange,
        ];
    }

    private function formatPercentageChange(float $change): string
    {
        $symbol = $change >= 0 ? '+' : '';
        return sprintf("%s%.1f%% dari kemarin", $symbol, $change);
    }
}
