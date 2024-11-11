<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\LaporanKeuangan;
use App\Models\Perusahaan;
use App\Models\Penjual;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // Hapus data yang mungkin duplikat
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            // Get saldo awal
            $saldoSekarang = Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->value('saldo') ?? 0;

            // Reset saldo awal untuk perhitungan bertahap
            $saldoBerjalan = $saldoSekarang;

            // 1. Catat pembayaran hutang sebagai pemasukan jika ada
            if ($transaksiDo->bayar_hutang > 0) {
                $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'masuk',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'bayar_hutang',
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->bayar_hutang,
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan + $transaksiDo->bayar_hutang,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);

                // Update saldo dan hutang
                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->increment('saldo', $transaksiDo->bayar_hutang);

                Penjual::where('id', $transaksiDo->penjual_id)
                    ->decrement('hutang', $transaksiDo->bayar_hutang);

                $saldoBerjalan += $transaksiDo->bayar_hutang;
            }

            // 2. Catat pengeluaran
            // a. Biaya Lain
            if ($transaksiDo->biaya_lain > 0) {
                $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'biaya_lain',
                    'keterangan' => "Biaya Lain DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->biaya_lain,
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan - $transaksiDo->biaya_lain,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);

                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->decrement('saldo', $transaksiDo->biaya_lain);

                $saldoBerjalan -= $transaksiDo->biaya_lain;
            }

            // b. Upah Bongkar
            if ($transaksiDo->upah_bongkar > 0) {
                $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'upah_bongkar',
                    'keterangan' => "Upah Bongkar DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->upah_bongkar,
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan - $transaksiDo->upah_bongkar,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);

                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->decrement('saldo', $transaksiDo->upah_bongkar);

                $saldoBerjalan -= $transaksiDo->upah_bongkar;
            }

            // c. Sisa Bayar
            if ($transaksiDo->sisa_bayar > 0) {
                $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'pembayaran_do',
                    'keterangan' => "Pembayaran DO #{$transaksiDo->nomor}",
                    'nominal' => $transaksiDo->sisa_bayar,
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan - $transaksiDo->sisa_bayar,
                    'transaksi_do_id' => $transaksiDo->id,
                ]);

                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->decrement('saldo', $transaksiDo->sisa_bayar);

                $saldoBerjalan -= $transaksiDo->sisa_bayar;
            }

            DB::commit();

            Notification::make()
                ->title('Transaksi DO Berhasil')
                ->body("Transaksi DO #{$transaksiDo->nomor} telah berhasil dicatat")
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error!')
                ->body('Terjadi kesalahan saat memproses transaksi')
                ->danger()
                ->send();
            throw $e;
        }
    }

    private function createLaporanKeuangan(array $data)
    {
        // Cek apakah data sudah ada
        $exists = LaporanKeuangan::where([
            'transaksi_do_id' => $data['transaksi_do_id'],
            'kategori_do' => $data['kategori_do'],
            'nominal' => $data['nominal'],
        ])->exists();

        // Jika belum ada, baru dibuat
        if (!$exists) {
            LaporanKeuangan::create($data);
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // Get semua record laporan keuangan terkait
            $records = LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->get();

            foreach ($records as $record) {
                // Kembalikan saldo berdasarkan jenis transaksi
                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->{$record->jenis === 'masuk' ? 'decrement' : 'increment'}('saldo', $record->nominal);
            }

            // Kembalikan hutang penjual jika ada pembayaran hutang
            if ($transaksiDo->bayar_hutang > 0) {
                Penjual::where('id', $transaksiDo->penjual_id)
                    ->increment('hutang', $transaksiDo->bayar_hutang);
            }

            // Hapus semua record laporan keuangan
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            DB::commit();

            Notification::make()
                ->title('Transaksi DO Dihapus')
                ->body("Transaksi DO #{$transaksiDo->nomor} telah dihapus")
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error!')
                ->body('Terjadi kesalahan saat menghapus transaksi')
                ->danger()
                ->send();
            throw $e;
        }
    }
}
