<?php

namespace App\Observers;

use Illuminate\Support\Str;
use App\Services\CacheService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{DB, Log};
use App\Observers\LaporanKeuanganObserver;
use App\Models\{TransaksiDo, LaporanKeuangan, Perusahaan, Penjual};

class TransaksiDoObserver
{
    public function creating(TransaksiDo $transaksiDo)
    {
        try {
            // 1. Validasi data wajib
            if (!$transaksiDo->tanggal) {
                throw new \Exception("Tanggal wajib diisi");
            }
            if (!$transaksiDo->penjual_id) {
                throw new \Exception("Penjual wajib dipilih");
            }
            if ($transaksiDo->tonase <= 0) {
                throw new \Exception("Tonase harus lebih dari 0");
            }
            if ($transaksiDo->harga_satuan <= 0) {
                throw new \Exception("Harga satuan harus lebih dari 0");
            }

            // 2. Simpan dan validasi hutang
            if ($transaksiDo->penjual_id) {
                $penjual = Penjual::find($transaksiDo->penjual_id);
                if (!$penjual) {
                    throw new \Exception("Penjual tidak ditemukan");
                }

                $transaksiDo->hutang_awal = $penjual->hutang ?? 0;

                if ($transaksiDo->pembayaran_hutang > $transaksiDo->hutang_awal) {
                    throw new \Exception(
                        "Pembayaran hutang (Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') .
                            ") melebihi hutang penjual (Rp " . number_format($transaksiDo->hutang_awal, 0, ',', '.') . ")"
                    );
                }

                // Hitung sisa hutang
                $transaksiDo->sisa_hutang_penjual = max(0, $transaksiDo->hutang_awal - $transaksiDo->pembayaran_hutang);
            }

            // 3. Hitung total dan sisa bayar
            $transaksiDo->total = $transaksiDo->tonase * $transaksiDo->harga_satuan;
            $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->pembayaran_hutang;
            $transaksiDo->sisa_bayar = max(0, $transaksiDo->total - $totalPemasukan);

            // 4. Validasi saldo perusahaan - UPDATED: hanya untuk cara bayar Tunai
            $perusahaan = Perusahaan::first();
            if (!$perusahaan) {
                throw new \Exception("Data perusahaan tidak ditemukan");
            }

            // Cek saldo hanya jika cara bayar Tunai
            if ($transaksiDo->cara_bayar === 'Tunai' && $transaksiDo->sisa_bayar > $perusahaan->saldo) {
                throw new \Exception(
                    "Saldo perusahaan tidak mencukupi untuk transaksi.\n" .
                        "Saldo saat ini: Rp " . number_format($perusahaan->saldo, 0, ',', '.') . "\n" .
                        "Dibutuhkan: Rp " . number_format($transaksiDo->sisa_bayar, 0, ',', '.')
                );
            }

            Log::info('Data DO Siap Disimpan:', [
                'nomor' => $transaksiDo->nomor,
                'penjual' => $penjual->nama ?? null,
                'total' => $transaksiDo->total,
                'pemasukan' => $totalPemasukan,
                'sisa_bayar' => $transaksiDo->sisa_bayar,
                'hutang_awal' => $transaksiDo->hutang_awal,
                'pembayaran_hutang' => $transaksiDo->pembayaran_hutang,
                'sisa_hutang' => $transaksiDo->sisa_hutang_penjual,
                'cara_bayar' => $transaksiDo->cara_bayar // Tambah log cara bayar
            ]);
        } catch (\Exception $e) {
            Log::error('Error Validasi TransaksiDO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }


    /**
     * Handle the TransaksiDo "created" event.
     */
    public function created(TransaksiDo $transaksiDo)
    {
        app(LaporanKeuanganObserver::class)->handleTransaksiDO($transaksiDo);
    }


    //--------Updating---------//
    /**
     * Handle the TransaksiDo "updating" event.
     */
    public function updating(TransaksiDo $transaksiDo)
    {
        // Validasi pembayaran hutang
        if ($transaksiDo->isDirty('pembayaran_hutang')) {
            if ($transaksiDo->pembayaran_hutang > $transaksiDo->hutang_awal) {
                throw new \Exception("Pembayaran hutang tidak boleh melebihi hutang awal");
            }
        }

        // Validasi perubahan cara bayar
        if ($transaksiDo->isDirty('cara_bayar')) {
            // Jika berubah ke Tunai, cek saldo
            if ($transaksiDo->cara_bayar === 'Tunai') {
                $perusahaan = Perusahaan::first();
                if ($transaksiDo->sisa_bayar > $perusahaan->saldo) {
                    throw new \Exception("Saldo tidak cukup untuk pembayaran tunai");
                }
            }
        }
    }

    public function updated(TransaksiDo $transaksiDo)
    {
        // Hapus laporan keuangan lama berdasarkan referensi_id
        LaporanKeuangan::where([
            'sumber_transaksi' => 'DO',
            'referensi_id' => $transaksiDo->id
        ])->delete();

        // Buat laporan keuangan baru
        app(LaporanKeuanganObserver::class)->handleTransaksiDO($transaksiDo);

        CacheService::clearTransaksiCache($transaksiDo->penjual_id);

        Log::info('TransaksiDO Updated:', [
            'nomor' => $transaksiDo->nomor,
            'changes' => $transaksiDo->getChanges(),
            'cara_bayar' => $transaksiDo->cara_bayar
        ]);
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            CacheService::clearTransaksiCache($transaksiDo->penjual_id);

            $dataPembatalan = [
                'nomor_do' => $transaksiDo->nomor,
                'pembayaran_hutang' => $transaksiDo->pembayaran_hutang,
                'cara_bayar' => $transaksiDo->cara_bayar,
                'laporan_keuangan' => []
            ];

            // Kembalikan hutang penjual
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                $hutangSebelum = $transaksiDo->penjual->hutang;
                $transaksiDo->penjual->increment('hutang', $transaksiDo->pembayaran_hutang);
                $hutangSesudah = $transaksiDo->penjual->fresh()->hutang;
            }

            // Proses pembatalan laporan keuangan
            $laporanKeuangan = LaporanKeuangan::where([
                'sumber_transaksi' => 'DO',
                'referensi_id' => $transaksiDo->id
            ])->get();

            foreach ($laporanKeuangan as $laporan) {
                $dataPembatalan['laporan_keuangan'][] = [
                    'id' => $laporan->id,
                    'jenis_transaksi' => $laporan->jenis_transaksi,
                    'nominal' => $laporan->nominal,
                    'kategori' => $laporan->kategori,
                    'sub_kategori' => $laporan->sub_kategori
                ];
            }

            // Hapus laporan terkait
            LaporanKeuangan::where([
                'sumber_transaksi' => 'DO',
                'referensi_id' => $transaksiDo->id
            ])->delete();

            DB::commit();

            $this->sendDeleteNotification($transaksiDo);

            Log::info('TransaksiDO Berhasil Dibatalkan:', $dataPembatalan);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Pembatalan TransaksiDO:', [
                'error' => $e->getMessage(),
                'data' => [
                    'transaksi' => $transaksiDo->toArray(),
                    'laporan_keuangan' => isset($laporanKeuangan) ? $laporanKeuangan->toArray() : null
                ]
            ]);
            throw $e;
        }
    }

    public function restored(TransaksiDo $transaksiDo)
    {
        CacheService::clearTransaksiCache($transaksiDo->penjual_id);
    }

    public function forceDeleted(TransaksiDo $transaksiDo)
    {
        CacheService::clearTransaksiCache($transaksiDo->penjual_id);
    }

    private function createLaporanKeuangan(array $data): ?LaporanKeuangan
    {
        try {
            // Validasi data wajib
            $requiredFields = [
                'tanggal',
                'jenis_transaksi',
                'kategori',
                'sub_kategori',
                'nominal',
                'sumber_transaksi',
                'referensi_id',
                'nomor_referensi',
                'pihak_terkait',
                'tipe_pihak',
                'cara_pembayaran',
                'keterangan',
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Field {$field} wajib diisi pada laporan keuangan");
                }
            }

            // Cek duplikasi
            if ($data['tipe_transaksi'] === 'transaksi_do' && isset($data['transaksi_do_id'])) {
                $exists = $this->checkDuplikasiTransaksi(
                    $data['transaksi_do_id'],
                    $data['kategori_do'] ?? null,
                    $data['nominal']
                );

                if ($exists) {
                    Log::info('Transaksi duplikat, dilewati:', [
                        'transaksi_do_id' => $data['transaksi_do_id'],
                        'kategori' => $data['kategori_do'] ?? null,
                        'nominal' => $data['nominal']
                    ]);
                    return null;
                }
            }

            $laporan = LaporanKeuangan::create($data);

            Log::info('Laporan Keuangan dibuat:', [
                'id' => $laporan->id,
                'jenis' => $laporan->jenis,
                'nominal' => $laporan->nominal,
                'mempengaruhi_kas' => $laporan->mempengaruhi_kas,
                'cara_pembayaran' => $laporan->cara_pembayaran,
                'saldo_sebelum' => $laporan->saldo_sebelum,
                'saldo_sesudah' => $laporan->saldo_sesudah
            ]);

            return $laporan;
        } catch (\Exception $e) {
            Log::error('Error membuat Laporan Keuangan:', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Check duplikasi transaksi
     */
    // Helper methods untuk check duplikasi dan notifikasi
    private function checkDuplikasiTransaksi(int $transaksiDoId, ?string $kategori, float $nominal): bool
    {
        $query = LaporanKeuangan::where('transaksi_do_id', $transaksiDoId)
            ->where('nominal', $nominal);

        if ($kategori) {
            $query->where('kategori_do', $kategori);
        }

        return $query->exists();
    }

    private function sendSuccessNotification(TransaksiDo $transaksiDo): void
    {
        $totalPemasukan = $transaksiDo->upah_bongkar + $transaksiDo->biaya_lain + $transaksiDo->pembayaran_hutang;
        $totalPengeluaran = $transaksiDo->sisa_bayar;
        $selisih = $totalPemasukan - $totalPengeluaran;

        $message = $this->buildNotificationMessage($transaksiDo, $totalPemasukan, $totalPengeluaran, $selisih);

        Notification::make()
            ->title('Transaksi DO Berhasil')
            ->body($message)
            ->success()
            ->send();
    }

    private function sendDeleteNotification(TransaksiDo $transaksiDo): void
    {
        $message = "DO #{$transaksiDo->nomor} telah dibatalkan\n\n";

        if ($transaksiDo->pembayaran_hutang > 0) {
            $message .= "Info Hutang:\n";
            $message .= "- Hutang dikembalikan: Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') . "\n";
            if ($transaksiDo->penjual) {
                $message .= "- Hutang terkini: Rp " . number_format($transaksiDo->penjual->hutang, 0, ',', '.') . "\n";
            }
        }

        // Tambahkan info cara bayar
        $message .= "\nCara Bayar: {$transaksiDo->cara_bayar}";
        if ($transaksiDo->cara_bayar === 'Tunai') {
            $message .= "\nSaldo kas telah dikembalikan.";
        }

        Notification::make()
            ->title('Transaksi DO Dibatalkan')
            ->body($message)
            ->warning()
            ->send();
    }

    private function buildNotificationMessage(TransaksiDo $transaksiDo, $totalPemasukan, $totalPengeluaran, $selisih): string
    {
        $message = "DO #{$transaksiDo->nomor}\n\n";
        $message .= "Detail Transaksi:\n";
        $message .= "- Tonase: " . number_format($transaksiDo->tonase, 0, ',', '.') . " Kg\n";
        $message .= "- Total DO: Rp " . number_format($transaksiDo->total, 0, ',', '.') . "\n";
        $message .= "- Cara Bayar: {$transaksiDo->cara_bayar}\n\n";

        $message .= "Pemasukan:\n";
        if ($transaksiDo->upah_bongkar > 0) {
            $message .= "- Upah Bongkar: Rp " . number_format($transaksiDo->upah_bongkar, 0, ',', '.') . "\n";
        }
        if ($transaksiDo->biaya_lain > 0) {
            $message .= "- Biaya Lain: Rp " . number_format($transaksiDo->biaya_lain, 0, ',', '.') . "\n";
        }
        if ($transaksiDo->pembayaran_hutang > 0) {
            $message .= "- Bayar Hutang: Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') . "\n";
        }
        $message .= "Total Pemasukan: Rp " . number_format($totalPemasukan, 0, ',', '.') . "\n\n";

        $message .= "Pengeluaran:\n";
        $message .= "- Sisa Bayar DO: Rp " . number_format($totalPengeluaran, 0, ',', '.') . "\n\n";

        $message .= "Selisih: Rp " . number_format($selisih, 0, ',', '.') . "\n";

        if ($transaksiDo->pembayaran_hutang > 0) {
            $message .= "\nInfo Hutang:\n";
            $message .= "- Hutang Awal: Rp " . number_format($transaksiDo->hutang_awal, 0, ',', '.') . "\n";
            $message .= "- Pembayaran: Rp " . number_format($transaksiDo->pembayaran_hutang, 0, ',', '.') . "\n";
            $message .= "- Sisa Hutang: Rp " . number_format($transaksiDo->sisa_hutang_penjual, 0, ',', '.');
        }

        // Tambah info kas
        if ($transaksiDo->cara_bayar === 'Tunai') {
            $message .= "\n\nTransaksi mempengaruhi saldo kas.";
        } else {
            $message .= "\n\nTransaksi tidak mempengaruhi saldo kas.";
        }

        return $message;
    }
}
