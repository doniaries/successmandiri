<?php

namespace App\Observers;

use App\Models\TransaksiDo;
use App\Models\LaporanKeuangan;
use App\Models\Perusahaan;
use App\Models\Penjual;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{DB, Log};

class TransaksiDoObserver
{
    public function creating(TransaksiDo $transaksiDo)
    {
        try {
            // 1. Simpan dan validasi hutang
            if ($transaksiDo->penjual_id) {
                $transaksiDo->hutang_awal = $transaksiDo->penjual->hutang ?? 0;

                if ($transaksiDo->pembayaran_hutang > $transaksiDo->hutang_awal) {
                    throw new \Exception(
                        "Pembayaran hutang (Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') .
                            ") melebihi hutang penjual (Rp " . number_format($transaksiDo->hutang_awal, 0, ',', '.') . ")"
                    );
                }
            }

            // 2. Hitung komponen keuangan
            $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->pembayaran_hutang;
            $totalPengeluaran = $transaksiDo->sisa_bayar;
            $selisihDibutuhkan = $totalPengeluaran - $totalPemasukan;

            // 3. Validasi saldo perusahaan
            $saldoPerusahaan = Perusahaan::first()->saldo ?? 0;
            if ($selisihDibutuhkan > $saldoPerusahaan) {
                throw new \Exception(
                    "Saldo tidak mencukupi untuk transaksi.\n" .
                        "Saldo saat ini: Rp " . number_format($saldoPerusahaan, 0, ',', '.') . "\n" .
                        "Dibutuhkan: Rp " . number_format($selisihDibutuhkan, 0, ',', '.')
                );
            }

            // 4. Set nilai-nilai default
            $transaksiDo->sisa_hutang_penjual = $transaksiDo->hitungSisaHutang();
            $transaksiDo->sisa_bayar = $transaksiDo->hitungSisaBayar();

            Log::info('TransaksiDO Creating:', [
                'penjual_id' => $transaksiDo->penjual_id,
                'hutang_awal' => $transaksiDo->hutang_awal,
                'pembayaran_hutang' => $transaksiDo->pembayaran_hutang,
                'sisa_hutang' => $transaksiDo->sisa_hutang_penjual
            ]);
        } catch (\Exception $e) {
            Log::error('Error Creating TransaksiDO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }

    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // Get & track saldo
            $saldoBerjalan = Perusahaan::first()->saldo ?? 0;

            Log::info('Mulai Proses Transaksi DO:', [
                'nomor_do' => $transaksiDo->nomor,
                'saldo_awal' => $saldoBerjalan
            ]);

            // 1. Proses pemasukan
            if ($transaksiDo->upah_bongkar > 0) {
                $this->prosesTransaksiKeuangan(
                    'masuk',
                    'upah_bongkar',
                    $transaksiDo->upah_bongkar,
                    "Pemasukan Upah Bongkar DO #{$transaksiDo->nomor}",
                    $transaksiDo,
                    $saldoBerjalan
                );
                $saldoBerjalan += $transaksiDo->upah_bongkar;
            }

            if ($transaksiDo->biaya_lain > 0) {
                $this->prosesTransaksiKeuangan(
                    'masuk',
                    'biaya_lain',
                    $transaksiDo->biaya_lain,
                    "Pemasukan Biaya Lain DO #{$transaksiDo->nomor}",
                    $transaksiDo,
                    $saldoBerjalan
                );
                $saldoBerjalan += $transaksiDo->biaya_lain;
            }

            // 2. Proses pembayaran hutang
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                // Catat riwayat
                RiwayatHutang::create([
                    'tipe_entitas' => 'penjual',
                    'entitas_id' => $transaksiDo->penjual_id,
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'jenis' => 'pengurangan',
                    'hutang_sebelum' => $transaksiDo->hutang_awal,
                    'hutang_sesudah' => $transaksiDo->sisa_hutang_penjual,
                    'keterangan' => "Pembayaran hutang via DO #{$transaksiDo->nomor}",
                    'transaksi_do_id' => $transaksiDo->id
                ]);

                // Update hutang penjual
                $transaksiDo->penjual->update(['hutang' => $transaksiDo->sisa_hutang_penjual]);

                // Catat di laporan keuangan
                $this->prosesTransaksiKeuangan(
                    'masuk',
                    'pembayaran_hutang',
                    $transaksiDo->pembayaran_hutang,
                    "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                    $transaksiDo,
                    $saldoBerjalan
                );
                $saldoBerjalan += $transaksiDo->pembayaran_hutang;
            }

            // 3. Proses pengeluaran/pembayaran DO
            if ($transaksiDo->sisa_bayar > 0) {
                $this->prosesTransaksiKeuangan(
                    'keluar',
                    'pembayaran_do',
                    $transaksiDo->sisa_bayar,
                    "Pembayaran DO #{$transaksiDo->nomor}",
                    $transaksiDo,
                    $saldoBerjalan
                );
                $saldoBerjalan -= $transaksiDo->sisa_bayar;
            }

            DB::commit();

            // 4. Kirim notifikasi sukses
            $this->sendSuccessNotification($transaksiDo);

            Log::info('Transaksi DO Selesai:', [
                'nomor_do' => $transaksiDo->nomor,
                'saldo_akhir' => $saldoBerjalan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Transaksi DO:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // 1. Kembalikan hutang penjual jika ada pembayaran
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                $transaksiDo->penjual->increment('hutang', $transaksiDo->pembayaran_hutang);

                // Catat di riwayat hutang
                RiwayatHutang::create([
                    'tipe_entitas' => 'penjual',
                    'entitas_id' => $transaksiDo->penjual_id,
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'jenis' => 'penambahan',
                    'hutang_sebelum' => $transaksiDo->penjual->hutang - $transaksiDo->pembayaran_hutang,
                    'hutang_sesudah' => $transaksiDo->penjual->hutang,
                    'keterangan' => "Pembatalan DO #{$transaksiDo->nomor}",
                    'transaksi_do_id' => $transaksiDo->id
                ]);
            }

            // 2. Kembalikan saldo perusahaan
            $perusahaan = Perusahaan::first();

            // Hapus/rollback semua transaksi keuangan
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            DB::commit();

            $this->sendDeleteNotification($transaksiDo);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Delete TransaksiDO:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    private function prosesTransaksiKeuangan(
        string $jenis,
        string $kategori,
        float $nominal,
        string $keterangan,
        TransaksiDo $transaksiDo,
        float $saldoBerjalan
    ) {
        // Log sebelum proses
        Log::info('Proses Transaksi Keuangan:', [
            'jenis' => $jenis,
            'kategori' => $kategori,
            'nominal' => $nominal,
            'saldo_sebelum' => $saldoBerjalan
        ]);

        // 1. Catat di laporan keuangan jika belum ada
        if (!$this->checkDuplikasiTransaksi($transaksiDo->id, $kategori, $nominal)) {
            $this->createLaporanKeuangan([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => $jenis,
                'tipe_transaksi' => 'transaksi_do',
                'kategori_do' => $kategori,
                'keterangan' => $keterangan,
                'nominal' => $nominal,
                'saldo_sebelum' => $saldoBerjalan,
                'saldo_sesudah' => $jenis === 'masuk' ? $saldoBerjalan + $nominal : $saldoBerjalan - $nominal,
                'transaksi_do_id' => $transaksiDo->id,
                // Tambah data penjual dan nomor transaksi
                'nomor_transaksi' => $transaksiDo->nomor,
                'nama_penjual' => $transaksiDo->penjual?->nama
            ]);

            // 2. Update saldo perusahaan
            $perusahaan = Perusahaan::first();
            if ($jenis === 'masuk') {
                $perusahaan->increment('saldo', $nominal);
            } else {
                $perusahaan->decrement('saldo', $nominal);
            }

            // Log setelah proses
            Log::info('Transaksi Berhasil:', [
                'kategori' => $kategori,
                'saldo_sesudah' => $perusahaan->fresh()->saldo,
                'nomor_transaksi' => $transaksiDo->nomor,
                'nama_penjual' => $transaksiDo->penjual?->nama
            ]);
        } else {
            Log::info('Skip Transaksi (Duplikat):', [
                'kategori' => $kategori,
                'nominal' => $nominal
            ]);
        }
    }

    private function createLaporanKeuangan(array $data)
    {
        return LaporanKeuangan::create($data);
    }

    private function sendSuccessNotification(TransaksiDo $transaksiDo)
    {
        $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->pembayaran_hutang;

        Notification::make()
            ->title('Transaksi DO Berhasil')
            ->body(
                "DO #{$transaksiDo->nomor}\n" .
                    "Pemasukan: Rp " . number_format($totalPemasukan, 0, ',', '.') . "\n" .
                    "Pengeluaran: Rp " . number_format($transaksiDo->sisa_bayar, 0, ',', '.') . "\n" .
                    "Selisih: Rp " . number_format($totalPemasukan - $transaksiDo->sisa_bayar, 0, ',', '.')
            )
            ->success()
            ->send();
    }

    private function sendDeleteNotification(TransaksiDo $transaksiDo)
    {
        Notification::make()
            ->title('Transaksi DO Dibatalkan')
            ->body("DO #{$transaksiDo->nomor} telah dibatalkan dan saldo dikembalikan")
            ->success()
            ->send();
    }
}
