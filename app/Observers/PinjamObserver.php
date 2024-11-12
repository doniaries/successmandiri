<?php

namespace App\Observers;

use App\Models\Pinjam;
use App\Models\Keuangan;

class PinjamObserver
{
    public function created(Pinjam $pinjam)
    {
        // Catat pengeluaran untuk pinjaman baru
        Keuangan::create([
            'tanggal' => $pinjam->tanggal_pinjaman,
            'jenis' => 'pengeluaran',
            'kategori' => '',
            'referensi_id' => $pinjam->id,
            'nominal' => $pinjam->nominal,
            'keterangan' => "Pinjaman {$pinjam->kategori_peminjam} - {$pinjam->nama_peminjam}",
            'created_by' => auth()->id(),
        ]);
    }

    public function deleted(Pinjam $pinjam)
    {
        // Hapus record keuangan terkait
        Keuangan::where('referensi_id', $pinjam->id)
            ->where('kategori', '')
            ->delete();
    }
}
