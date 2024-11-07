<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\Keuangan;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
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

        // Catat pemasukan jika ada pembayaran hutang
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

    public function updated(TransaksiDo $transaksiDo)
    {
        // Handle perubahan transaksi
        if ($transaksiDo->isDirty(['total', 'bayar_hutang'])) {
            // Update atau hapus record keuangan yang terkait
            Keuangan::where('referensi_id', $transaksiDo->id)
                ->where('kategori', 'transaksi_do')
                ->delete();

            // Buat record baru
            $this->created($transaksiDo);
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        // Hapus record keuangan terkait
        Keuangan::where('referensi_id', $transaksiDo->id)->delete();
    }
}
