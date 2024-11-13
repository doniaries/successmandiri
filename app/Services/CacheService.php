<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;
use App\Models\{Penjual, TransaksiDo};

class CacheService
{
    const CACHE_TIME = 300; // 5 menit
    const PREFIX = 'transaksi_do_';

    /**
     * Clear cache yang berkaitan dengan transaksi
     */
    public static function clearTransaksiCache($penjualId): void
    {
        try {
            $keys = [
                self::PREFIX . "stats_{$penjualId}",
                self::PREFIX . "hutang_{$penjualId}",
                self::PREFIX . "transaksi_{$penjualId}",
                self::PREFIX . 'summary',
                self::PREFIX . 'dashboard',
                self::PREFIX . 'laporan_harian'
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            // Clear cache global
            Cache::tags(['transaksi_do'])->flush();

            Log::info('Cache cleared successfully', [
                'penjual_id' => $penjualId,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cache:', [
                'error' => $e->getMessage(),
                'penjual_id' => $penjualId
            ]);
        }
    }

    /**
     * Get statistik penjual dengan cache
     */
    public static function getPenjualStats($penjualId)
    {
        $key = self::PREFIX . "stats_{$penjualId}";

        return Cache::remember($key, self::CACHE_TIME, function () use ($penjualId) {
            return Penjual::with([
                'transaksiDo' => function ($q) {
                    $q->latest()->take(5);
                },
                'riwayatHutang' => function ($q) {
                    $q->latest()->take(5);
                }
            ])
                ->withCount('transaksiDo')
                ->withSum('transaksiDo', 'total')
                ->withSum('transaksiDo', 'pembayaran_hutang')
                ->find($penjualId);
        });
    }

    /**
     * Get ringkasan transaksi
     */
    public static function getTransaksiSummary()
    {
        return Cache::remember(self::PREFIX . 'summary', self::CACHE_TIME, function () {
            return TransaksiDo::select(
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total) as total_nilai'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('AVG(harga_satuan) as rata_harga'),
                DB::raw('SUM(pembayaran_hutang) as total_pembayaran_hutang'),
                DB::raw('SUM(sisa_bayar) as total_sisa_bayar')
            )
                ->whereDate('created_at', Carbon::today())
                ->first();
        });
    }

    /**
     * Get data dashboard
     */
    public static function getDashboardStats()
    {
        return Cache::remember(self::PREFIX . 'dashboard', self::CACHE_TIME, function () {
            return [
                'total_transaksi' => TransaksiDo::count(),
                'total_hutang' => Penjual::sum('hutang'),
                'transaksi_hari_ini' => TransaksiDo::whereDate('tanggal', Carbon::today())->count(),
                'pembayaran_hutang_hari_ini' => TransaksiDo::whereDate('tanggal', Carbon::today())
                    ->sum('pembayaran_hutang')
            ];
        });
    }
}
