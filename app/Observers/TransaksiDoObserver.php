<?php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\TransaksiDo;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // 1. Catat total DO sebagai pengeluaran
            Operasional::create([
                'tanggal' => $transaksiDo->tanggal,
                'operasional' => 'pengeluaran',
                'kategori_id' => KategoriOperasional::TOTAL_DO,
                'tipe_nama' => 'penjual',
                'penjual_id' => $transaksiDo->penjual_id,
                'nominal' => $transaksiDo->total,
                'keterangan' => "Total DO #{$transaksiDo->nomor}",
                'is_from_transaksi' => true,
            ]);

            // 2. Jika ada pembayaran hutang
            if ($transaksiDo->bayar_hutang > 0) {
                Operasional::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'operasional' => 'pemasukan',
                    'kategori_id' => KategoriOperasional::BAYAR_HUTANG,
                    'tipe_nama' => 'penjual',
                    'penjual_id' => $transaksiDo->penjual_id,
                    'nominal' => $transaksiDo->bayar_hutang,
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                    'is_from_transaksi' => true,
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