<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\Keuangan;
use Illuminate\Support\Facades\Log;

class TransaksiDoObserver
{
    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            // Hapus semua transaksi keuangan terkait
            Keuangan::where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->delete();

            Log::info('Transaksi DO dihapus:', [
                'nomor' => $transaksiDo->nomor
            ]);
        } catch (\Exception $e) {
            Log::error('Error menghapus transaksi DO:', [
                'nomor' => $transaksiDo->nomor,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function restored(TransaksiDo $transaksiDo)
    {
        try {
            // Restore transaksi keuangan terkait
            Keuangan::onlyTrashed()
                ->where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->restore();

            Log::info('Transaksi DO dipulihkan:', [
                'nomor' => $transaksiDo->nomor
            ]);
        } catch (\Exception $e) {
            Log::error('Error memulihkan transaksi DO:', [
                'nomor' => $transaksiDo->nomor,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function forceDeleted(TransaksiDo $transaksiDo)
    {
        try {
            // Hapus permanen transaksi keuangan
            Keuangan::withTrashed()
                ->where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->forceDelete();

            Log::info('Transaksi DO dihapus permanen:', [
                'nomor' => $transaksiDo->nomor
            ]);
        } catch (\Exception $e) {
            Log::error('Error menghapus permanen transaksi DO:', [
                'nomor' => $transaksiDo->nomor,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}