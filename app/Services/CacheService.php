<?php
// app/Services/CacheService.php
class CacheService
{
    const CACHE_TIME = 300; // 5 menit

    public static function getPenjualStats($penjualId)
    {
        $cacheKey = "penjual_stats_{$penjualId}";

        return Cache::remember($cacheKey, self::CACHE_TIME, function () use ($penjualId) {
            return Penjual::with(['transaksiDo', 'riwayatHutang'])
                ->withCount('transaksiDo')
                ->withSum('transaksiDo', 'total')
                ->find($penjualId);
        });
    }

    public static function getTransaksiSummary()
    {
        return Cache::remember('transaksi_summary', self::CACHE_TIME, function () {
            return TransaksiDo::select(
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('SUM(total) as total_nilai'),
                DB::raw('SUM(tonase) as total_tonase'),
                DB::raw('AVG(harga_satuan) as rata_harga')
            )->whereDate('created_at', Carbon::today())
                ->first();
        });
    }

    // Flush cache when needed
    public static function flushPenjualCache($penjualId)
    {
        Cache::forget("penjual_stats_{$penjualId}");
        Cache::forget('transaksi_summary');
    }
}
