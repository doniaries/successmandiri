<?php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\TransaksiDo;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        // 1. Jika cara bayar transfer, catat sebagai pemasukan ke saldo perusahaan
        if ($transaksiDo->cara_bayar === 'Transfer') {
            Operasional::create([
                'tanggal' => $transaksiDo->tanggal,
                'operasional' => 'pemasukan',
                'kategori_id' => 7, // Kategori Lain-lain
                'tipe_nama' => 'penjual',
                'penjual_id' => $transaksiDo->penjual_id,
                'nominal' => $transaksiDo->sisa_bayar,
                'keterangan' => "Transfer masuk DO #{$transaksiDo->nomor}",
            ]);

            // Catat pengeluaran untuk transaksi DO
            Operasional::create([
                'tanggal' => $transaksiDo->tanggal,
                'operasional' => 'pengeluaran',
                'kategori_id' => 2, // Kategori Uang Jalan
                'tipe_nama' => 'penjual',
                'penjual_id' => $transaksiDo->penjual_id,
                'nominal' => $transaksiDo->total,
                'keterangan' => "Transaksi DO #{$transaksiDo->nomor}",
            ]);
        } else {
            // Jika bukan transfer, catat hanya sebagai pengeluaran biasa
            Operasional::create([
                'tanggal' => $transaksiDo->tanggal,
                'operasional' => 'pengeluaran',
                'kategori_id' => 2, // Kategori Uang Jalan
                'tipe_nama' => 'penjual',
                'penjual_id' => $transaksiDo->penjual_id,
                'nominal' => $transaksiDo->sisa_bayar,
                'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} via {$transaksiDo->cara_bayar}",
            ]);
        }

        // 2. Jika ada pembayaran hutang, catat sebagai pemasukan
        if ($transaksiDo->bayar_hutang > 0) {
            Operasional::create([
                'tanggal' => $transaksiDo->tanggal,
                'operasional' => 'pemasukan',
                'kategori_id' => 6, // Kategori Bayar Hutang
                'tipe_nama' => 'penjual',
                'penjual_id' => $transaksiDo->penjual_id,
                'nominal' => $transaksiDo->bayar_hutang,
                'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
            ]);
        }
    }
}
