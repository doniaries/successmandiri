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
            // 1. Hitung komponen pemasukan
            $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->bayar_hutang;

            // 2. Hitung komponen pengeluaran
            $totalPengeluaran = $transaksiDo->sisa_bayar;

            // 3. Hitung selisih yang dibutuhkan dari saldo
            $selisihDibutuhkan = $totalPengeluaran - $totalPemasukan;

            // 4. Ambil saldo perusahaan - PERBAIKAN CARA MENGAMBIL SALDO
            $saldoPerusahaan = Perusahaan::first()->saldo ?? 0;

            // Debug log untuk memastikan saldo terambil
            Log::info('Validasi Saldo Transaksi DO:', [
                'saldo_perusahaan' => $saldoPerusahaan,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'selisih_dibutuhkan' => $selisihDibutuhkan
            ]);

            // 5. Validasi saldo dengan pesan yang lebih detail
            if ($selisihDibutuhkan > $saldoPerusahaan) {
                throw new \Exception(
                    "Saldo tidak mencukupi untuk transaksi DO.\n" .
                        "Saldo saat ini: Rp " . number_format($saldoPerusahaan, 0, ',', '.') . "\n" .
                        "Total pemasukan: Rp " . number_format($totalPemasukan, 0, ',', '.') . "\n" .
                        "Total pengeluaran: Rp " . number_format($totalPengeluaran, 0, ',', '.') . "\n" .
                        "Kekurangan: Rp " . number_format($selisihDibutuhkan - $saldoPerusahaan, 0, ',', '.')
                );
            }

            // 6. Validasi bayar hutang tidak melebihi hutang yang ada
            if ($transaksiDo->penjual_id && $transaksiDo->bayar_hutang > 0) {
                $hutangPenjual = Penjual::find($transaksiDo->penjual_id)->hutang ?? 0;

                if ($transaksiDo->bayar_hutang > $hutangPenjual) {
                    throw new \Exception(
                        "Pembayaran hutang (Rp " . number_format($transaksiDo->bayar_hutang, 0, ',', '.') .
                            ") melebihi hutang yang ada (Rp " . number_format($hutangPenjual, 0, ',', '.') . ")"
                    );
                }
            }
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Error validasi Transaksi DO:', [
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

            // Hapus record yang mungkin duplikat
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            // Get & track saldo
            $saldoBerjalan = Perusahaan::first()->saldo ?? 0;

            Log::info('Mulai Proses Transaksi DO:', [
                'nomor_do' => $transaksiDo->nomor,
                'saldo_awal' => $saldoBerjalan
            ]);

            // 1. Proses semua pemasukan dulu

            // a. Upah Bongkar
            if ($transaksiDo->upah_bongkar > 0) {
                // Check duplikasi sebelum membuat record
                $exists = $this->checkDuplikasiTransaksi(
                    $transaksiDo->id,
                    'upah_bongkar',
                    $transaksiDo->upah_bongkar
                );

                if (!$exists) {
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
            }

            // b. Biaya Lain
            if ($transaksiDo->biaya_lain > 0) {
                $exists = $this->checkDuplikasiTransaksi(
                    $transaksiDo->id,
                    'biaya_lain',
                    $transaksiDo->biaya_lain
                );

                if (!$exists) {
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
            }

            // c. Bayar Hutang
            if ($transaksiDo->bayar_hutang > 0) {
                $exists = $this->checkDuplikasiTransaksi(
                    $transaksiDo->id,
                    'bayar_hutang',
                    $transaksiDo->bayar_hutang
                );

                if (!$exists) {
                    $this->prosesTransaksiKeuangan(
                        'masuk',
                        'bayar_hutang',
                        $transaksiDo->bayar_hutang,
                        "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                        $transaksiDo,
                        $saldoBerjalan
                    );

                    // Update hutang penjual
                    Penjual::where('id', $transaksiDo->penjual_id)
                        ->decrement('hutang', $transaksiDo->bayar_hutang);

                    $saldoBerjalan += $transaksiDo->bayar_hutang;
                }
            }

            // 2. Proses pengeluaran
            if ($transaksiDo->sisa_bayar > 0) {
                $exists = $this->checkDuplikasiTransaksi(
                    $transaksiDo->id,
                    'pembayaran_do',
                    $transaksiDo->sisa_bayar
                );

                if (!$exists) {
                    $this->prosesTransaksiKeuangan(
                        'keluar',
                        'pembayaran_do',
                        $transaksiDo->sisa_bayar,
                        "Pembayaran Sisa DO #{$transaksiDo->nomor}",
                        $transaksiDo,
                        $saldoBerjalan
                    );
                    $saldoBerjalan -= $transaksiDo->sisa_bayar;
                }
            }

            DB::commit();

            Log::info('Transaksi DO Selesai:', [
                'nomor_do' => $transaksiDo->nomor,
                'saldo_akhir' => $saldoBerjalan
            ]);

            // Notifikasi dengan detail
            $this->sendSuccessNotification($transaksiDo);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error memproses Transaksi DO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);

            Notification::make()
                ->title('Error!')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    // Method baru untuk cek duplikasi
    private function checkDuplikasiTransaksi(int $transaksiDoId, string $kategori, float $nominal): bool
    {
        return LaporanKeuangan::where('transaksi_do_id', $transaksiDoId)
            ->where('kategori_do', $kategori)
            ->where('nominal', $nominal)
            ->exists();
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // 1. Ambil semua record laporan keuangan
            $records = LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->get();

            // 2. Kembalikan saldo untuk setiap record
            foreach ($records as $record) {
                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->{$record->jenis === 'masuk' ? 'decrement' : 'increment'}('saldo', $record->nominal);
            }

            // 3. Kembalikan hutang penjual jika ada pembayaran
            if ($transaksiDo->bayar_hutang > 0) {
                Penjual::where('id', $transaksiDo->penjual_id)
                    ->increment('hutang', $transaksiDo->bayar_hutang);
            }

            // 4. Hapus records laporan keuangan
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            DB::commit();

            $this->sendDeleteNotification($transaksiDo);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error menghapus Transaksi DO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);

            Notification::make()
                ->title('Error!')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

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
                'saldo_sesudah' => $perusahaan->fresh()->saldo
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
        $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->bayar_hutang;

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
