<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\LaporanKeuangan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // 1. Catat pembayaran hutang sebagai pemasukan jika ada
            if ($transaksiDo->bayar_hutang > 0) {
                LaporanKeuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'masuk',
                    'kategori' => 'bayar_hutang',
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->bayar_hutang,
                    'sumber_transaksi' => 'transaksi_do',
                    'sumber_id' => $transaksiDo->id,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);
            }

            // 2. Catat pengeluaran
            // a. Biaya Lain
            if ($transaksiDo->biaya_lain > 0) {
                LaporanKeuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'kategori' => 'biaya_lain',
                    'keterangan' => "Biaya Lain DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->biaya_lain,
                    'sumber_transaksi' => 'transaksi_do',
                    'sumber_id' => $transaksiDo->id,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);
            }

            // b. Upah Bongkar
            if ($transaksiDo->upah_bongkar > 0) {
                LaporanKeuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'kategori' => 'upah_bongkar',
                    'keterangan' => "Upah Bongkar DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->upah_bongkar,
                    'sumber_transaksi' => 'transaksi_do',
                    'sumber_id' => $transaksiDo->id,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);
            }

            // c. Sisa Bayar
            if ($transaksiDo->sisa_bayar > 0) {
                LaporanKeuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'kategori' => 'pembayaran_do',
                    'keterangan' => "Pembayaran DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->sisa_bayar,
                    'sumber_transaksi' => 'transaksi_do',
                    'sumber_id' => $transaksiDo->id,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);
            }

            // 3. Update saldo perusahaan
            $totalPengeluaran = $transaksiDo->biaya_lain + $transaksiDo->upah_bongkar + $transaksiDo->sisa_bayar;
            $totalPemasukan = $transaksiDo->bayar_hutang;
            $selisih = $totalPemasukan - $totalPengeluaran;

            Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->increment('saldo', $selisih);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        // Hapus semua record terkait di laporan keuangan
        LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

        // Kembalikan saldo perusahaan
        $totalPengeluaran = $transaksiDo->biaya_lain + $transaksiDo->upah_bongkar + $transaksiDo->sisa_bayar;
        $totalPemasukan = $transaksiDo->bayar_hutang;
        $selisih = $totalPemasukan - $totalPengeluaran;

        Perusahaan::query()
            ->where('id', auth()->user()->perusahaan_id)
            ->decrement('saldo', $selisih);
    }
}
