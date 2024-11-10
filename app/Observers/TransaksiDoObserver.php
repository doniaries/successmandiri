<?php

namespace App\Observers;

use App\Models\Keuangan;
use App\Models\TransaksiDo;
use Illuminate\Support\Facades\DB;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // 1. Catat total DO sebagai pengeluaran
            Keuangan::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis_transaksi' => 'Keluar',
                'kategori' => 'Total DO',
                'sumber' => 'Penjual',
                'jumlah' => $transaksiDo->total,
                'keterangan' => "Total DO #{$transaksiDo->nomor}"
            ]);

            // 2. Jika ada pembayaran hutang
            if ($transaksiDo->bayar_hutang > 0) {
                Keuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis_transaksi' => 'Masuk',
                    'kategori' => 'Bayar Hutang',
                    'sumber' => 'Penjual',
                    'jumlah' => $transaksiDo->bayar_hutang,
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}"
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error recording transaction: ' . $e->getMessage(), [
                'transaksi_do' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }
}
