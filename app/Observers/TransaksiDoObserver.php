<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\Keuangan;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {

        // 1. Jika cara bayar transfer, catat sebagai pemasukan ke saldo perusahaan
        if ($transaksiDo->cara_bayar === 'Transfer') {
            Keuangan::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pemasukan',
                'kategori' => 'transfer_masuk',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->sisa_bayar,
                'keterangan' => "Transfer masuk DO #{$transaksiDo->nomor}",
                'created_by' => auth()->id(),
            ]);
            // Catat pengeluaran untuk transaksi DO
            Keuangan::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pengeluaran',
                'kategori' => 'transaksi_do',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->total,
                'keterangan' => "Transaksi DO #{$transaksiDo->nomor}",
                'created_by' => auth()->id(),
            ]);
        } else {
            // Jika bukan transfer, catat hanya sebagai pengeluaran biasa
            Keuangan::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pengeluaran',
                'kategori' => 'transaksi_do',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->sisa_bayar,
                'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} via {$transaksiDo->cara_bayar}",
                'created_by' => auth()->id(),
            ]);
        }

        // 2. Jika ada pembayaran hutang, catat sebagai pemasukan
        if ($transaksiDo->bayar_hutang > 0) {
            Keuangan::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pemasukan',
                'kategori' => 'bayar_hutang',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->bayar_hutang,
                'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function created(TransaksiDo $transaksiDo)
    {
        Keuangan::catatTransaksiDO($transaksiDo);
    }

    public function updated(TransaksiDo $transaksiDo)
    {
        // Hapus record keuangan yang lama
        Keuangan::where('referensi_id', $transaksiDo->id)->delete();

        // Buat ulang record
        Keuangan::catatTransaksiDO($transaksiDo);
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        // Hapus semua record keuangan terkait
        Keuangan::where('referensi_id', $transaksiDo->id)->delete();
    }
}
