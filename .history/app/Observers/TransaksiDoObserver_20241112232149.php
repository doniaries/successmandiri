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
        // Simpan hutang awal dari penjual
        if ($transaksiDo->penjual_id) {
            $transaksiDo->hutang_awal = $transaksiDo->penjual->hutang ?? 0;
        }

        // Validasi pembayaran hutang
        if ($transaksiDo->pembayaran_hutang > $transaksiDo->hutang_awal) {
            throw new \Exception(
                "Pembayaran hutang (Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') .
                    ") melebihi hutang penjual (Rp " . number_format($transaksiDo->hutang_awal, 0, ',', '.') . ")"
            );
        }

        // Hitung sisa hutang
        $transaksiDo->sisa_hutang_penjual = $transaksiDo->hitungSisaHutang();
    }

    public function created(TransaksiDo $transaksiDo)
    {
        DB::transaction(function () use ($transaksiDo) {
            // Update hutang penjual
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                // Catat di riwayat hutang
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
                $transaksiDo->penjual->update([
                    'hutang' => $transaksiDo->sisa_hutang_penjual
                ]);
            }
        });
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

            // Log awal proses
            Log::info('Mulai Pembatalan Transaksi DO:', [
                'nomor_do' => $transaksiDo->nomor,
                'data_transaksi' => $transaksiDo->toArray()
            ]);

            // 1. Ambil data perusahaan dan saldo awal
            $perusahaan = Perusahaan::first();
            $saldoAwal = $perusahaan->saldo;

            // 2. Ambil data penjual
            $penjual = Penjual::find($transaksiDo->penjual_id);
            $hutangAwal = $penjual ? $penjual->hutang : 0;

            // 3. Ambil semua record laporan keuangan
            $records = LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)
                ->orderBy('id', 'desc')  // Proses dari yang terakhir
                ->get();

            Log::info('Data Sebelum Pembatalan:', [
                'saldo_perusahaan' => $saldoAwal,
                'hutang_penjual' => $hutangAwal,
                'records_to_rollback' => $records->count()
            ]);

            // 4. Kembalikan saldo dan hutang sesuai transaksi
            foreach ($records as $record) {
                // A. Kembalikan saldo perusahaan
                if ($record->jenis === 'masuk') {
                    // Jika masuk, kurangi saldo
                    $perusahaan->decrement('saldo', $record->nominal);
                    Log::info('Mengurangi Saldo:', [
                        'nominal' => $record->nominal,
                        'kategori' => $record->kategori_do
                    ]);
                } else {
                    // Jika keluar, tambah saldo
                    $perusahaan->increment('saldo', $record->nominal);
                    Log::info('Menambah Saldo:', [
                        'nominal' => $record->nominal,
                        'kategori' => $record->kategori_do
                    ]);
                }

                // B. Kembalikan hutang jika ada pembayaran hutang
                if ($record->kategori_do === 'bayar_hutang' && $penjual) {
                    $penjual->increment('hutang', $record->nominal);
                    Log::info('Mengembalikan Hutang:', [
                        'nominal' => $record->nominal,
                        'penjual' => $penjual->nama
                    ]);
                }
            }

            // 5. Hapus semua record laporan keuangan
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            // 6. Verifikasi saldo dan hutang
            $perusahaan->refresh();
            if ($penjual) {
                $penjual->refresh();
            }

            Log::info('Data Setelah Pembatalan:', [
                'saldo_akhir' => $perusahaan->saldo,
                'hutang_akhir' => $penjual ? $penjual->hutang : 0,
                'records_deleted' => $records->count()
            ]);

            DB::commit();

            // 7. Kirim notifikasi sukses dengan detail
            Notification::make()
                ->title('Transaksi DO Dibatalkan')
                ->body(
                    "DO #{$transaksiDo->nomor} telah dibatalkan\n" .
                        "Saldo dikembalikan ke: Rp " . number_format($perusahaan->saldo, 0, ',', '.') . "\n" .
                        ($penjual ? "Hutang dikembalikan ke: Rp " . number_format($penjual->hutang, 0, ',', '.') : '')
                )
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error Pembatalan Transaksi DO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);

            Notification::make()
                ->title('Error!')
                ->body('Terjadi kesalahan saat membatalkan transaksi: ' . $e->getMessage())
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
