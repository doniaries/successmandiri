<?php

namespace App\Observers;

use App\Services\CacheService;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{DB, Log};
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
        try {
            DB::beginTransaction();

            // Clear cache menggunakan CacheService
            CacheService::clearTransaksiCache($transaksiDo->penjual_id);

            $perusahaan = Perusahaan::first();
            if (!$perusahaan) {
                throw new \Exception("Data perusahaan tidak ditemukan");
            }

            $saldoBerjalan = $perusahaan->saldo;

            // Proses berdasarkan cara bayar
            switch ($transaksiDo->cara_bayar) {
                case 'Tunai':
                    // 1. Proses Pemasukan Tunai (upah bongkar & biaya lain)
                    $komponenPemasukan = [
                        'upah_bongkar' => [
                            'nominal' => $transaksiDo->upah_bongkar,
                            'keterangan' => "Pemasukan Upah Bongkar (Tunai)"
                        ],
                        'biaya_lain' => [
                            'nominal' => $transaksiDo->biaya_lain,
                            'keterangan' => "Pemasukan Biaya Lain (Tunai)"
                        ]
                    ];

                    foreach ($komponenPemasukan as $kategori => $data) {
                        if ($data['nominal'] > 0) {
                            // Catat laporan keuangan untuk pemasukan
                            $this->createLaporanKeuangan([
                                'tanggal' => $transaksiDo->tanggal,
                                'jenis' => 'masuk',
                                'tipe_transaksi' => 'transaksi_do',
                                'kategori_do' => $kategori,
                                'nominal' => $data['nominal'],
                                'keterangan' => $data['keterangan'] . " DO #{$transaksiDo->nomor}",
                                'saldo_sebelum' => $saldoBerjalan,
                                'saldo_sesudah' => $saldoBerjalan + $data['nominal'],
                                'transaksi_do_id' => $transaksiDo->id,
                                'nomor_transaksi' => $transaksiDo->nomor,
                                'nama_penjual' => $transaksiDo->penjual?->nama,
                                'mempengaruhi_kas' => true,
                                'cara_pembayaran' => 'Tunai'
                            ]);

                            // Update saldo perusahaan
                            $perusahaan->increment('saldo', $data['nominal']);
                            $saldoBerjalan += $data['nominal'];
                        }
                    }

                    // Proses pembayaran sisa DO tunai
                    if ($transaksiDo->sisa_bayar > 0) {
                        $this->createLaporanKeuangan([
                            'tanggal' => $transaksiDo->tanggal,
                            'jenis' => 'keluar',
                            'tipe_transaksi' => 'transaksi_do',
                            'kategori_do' => 'pembayaran_do',
                            'nominal' => $transaksiDo->sisa_bayar,
                            'keterangan' => "Pembayaran Sisa DO #{$transaksiDo->nomor} (Tunai)",
                            'saldo_sebelum' => $saldoBerjalan,
                            'saldo_sesudah' => $saldoBerjalan - $transaksiDo->sisa_bayar,
                            'transaksi_do_id' => $transaksiDo->id,
                            'nomor_transaksi' => $transaksiDo->nomor,
                            'nama_penjual' => $transaksiDo->penjual->nama,
                            'mempengaruhi_kas' => true,
                            'cara_pembayaran' => 'Tunai'
                        ]);

                        // Update saldo perusahaan
                        $perusahaan->decrement('saldo', $transaksiDo->sisa_bayar);
                        $saldoBerjalan -= $transaksiDo->sisa_bayar;
                    }
                    break;

                case 'Transfer':
                    // Catat transaksi tanpa mempengaruhi saldo kas
                    if ($transaksiDo->sisa_bayar > 0) {
                        $this->createLaporanKeuangan([
                            'tanggal' => $transaksiDo->tanggal,
                            'jenis' => 'keluar',
                            'tipe_transaksi' => 'transaksi_do',
                            'kategori_do' => 'pembayaran_do',
                            'nominal' => $transaksiDo->sisa_bayar,
                            'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} via Transfer",
                            'saldo_sebelum' => $saldoBerjalan,
                            'saldo_sesudah' => $saldoBerjalan, // Tidak mempengaruhi saldo
                            'transaksi_do_id' => $transaksiDo->id,
                            'nomor_transaksi' => $transaksiDo->nomor,
                            'nama_penjual' => $transaksiDo->penjual->nama,
                            'mempengaruhi_kas' => false,
                            'cara_pembayaran' => 'Transfer'
                        ]);
                    }
                    break;

                case 'Cair di Luar':
                    // Hanya catat transaksi tanpa mempengaruhi saldo
                    $this->createLaporanKeuangan([
                        'tanggal' => $transaksiDo->tanggal,
                        'jenis' => 'keluar',
                        'tipe_transaksi' => 'transaksi_do',
                        'kategori_do' => 'pembayaran_do',
                        'nominal' => $transaksiDo->total,
                        'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} (Cair di Luar)",
                        'saldo_sebelum' => $saldoBerjalan,
                        'saldo_sesudah' => $saldoBerjalan, // Tidak mempengaruhi saldo
                        'transaksi_do_id' => $transaksiDo->id,
                        'nomor_transaksi' => $transaksiDo->nomor,
                        'nama_penjual' => $transaksiDo->penjual->nama,
                        'mempengaruhi_kas' => false,
                        'cara_pembayaran' => 'Cair di Luar'
                    ]);
                    break;
            }

            // Proses Pembayaran Hutang (Selalu mempengaruhi kas)
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'masuk',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'pembayaran_hutang',
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan + $transaksiDo->pembayaran_hutang,
                    'transaksi_do_id' => $transaksiDo->id,
                    'nomor_transaksi' => $transaksiDo->nomor,
                    'nama_penjual' => $transaksiDo->penjual->nama,
                    'mempengaruhi_kas' => true,
                    'cara_pembayaran' => 'Tunai'  // Pembayaran hutang selalu tunai
                ]);

                // Update saldo dan hutang
                $perusahaan->increment('saldo', $transaksiDo->pembayaran_hutang);
                $saldoBerjalan += $transaksiDo->pembayaran_hutang;
                $transaksiDo->penjual->decrement('hutang', $transaksiDo->pembayaran_hutang);
            }

            DB::commit();

            // Kirim notifikasi sukses
            $this->sendSuccessNotification($transaksiDo);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Proses TransaksiDO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }


    //--------Updating---------//
    /**
     * Handle the TransaksiDo "updating" event.
     */
    public function updating(TransaksiDo $transaksiDo)
    {
        // Validasi sebelum update jika diperlukan
        if ($transaksiDo->isDirty('pembayaran_hutang')) {
            if ($transaksiDo->pembayaran_hutang > $transaksiDo->hutang_awal) {
                throw new \Exception("Pembayaran hutang tidak boleh melebihi hutang awal");
            }
        }
    }

    /**
     * Handle the TransaksiDo "updated" event.
     */
    public function updated(TransaksiDo $transaksiDo)
    {
        // Clear cache menggunakan CacheService
        CacheService::clearTransaksiCache($transaksiDo->penjual_id);

        Log::info('TransaksiDO Updated:', [
            'nomor' => $transaksiDo->nomor,
            'changes' => $transaksiDo->getChanges()
        ]);
    }

    /**
     * Handle the TransaksiDo "deleted" event.
     */
    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            // Clear cache menggunakan CacheService
            CacheService::clearTransaksiCache($transaksiDo->penjual_id);

            // 1. Simpan data awal untuk log
            $dataPembatalan = [
                'nomor_do' => $transaksiDo->nomor,
                'pembayaran_hutang' => $transaksiDo->pembayaran_hutang,
                'laporan_keuangan' => []
            ];

            // 2. Kembalikan hutang penjual jika ada pembayaran
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                $hutangSebelum = $transaksiDo->penjual->hutang;
                $transaksiDo->penjual->increment('hutang', $transaksiDo->pembayaran_hutang);
                $hutangSesudah = $transaksiDo->penjual->fresh()->hutang;
            }

            // 3. Proses pembatalan laporan keuangan
            $laporanKeuangan = LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->get();
            $perusahaan = Perusahaan::first();

            foreach ($laporanKeuangan as $laporan) {
                $saldoSebelum = $perusahaan->saldo;

                if ($laporan->jenis === 'masuk') {
                    $perusahaan->decrement('saldo', $laporan->nominal);
                } else {
                    $perusahaan->increment('saldo', $laporan->nominal);
                }

                $dataPembatalan['laporan_keuangan'][] = [
                    'id' => $laporan->id,
                    'jenis' => $laporan->jenis,
                    'nominal' => $laporan->nominal,
                    'saldo_sebelum' => $saldoSebelum,
                    'saldo_sesudah' => $perusahaan->fresh()->saldo
                ];
            }

            // // Hapus riwayat hutang yang terkait dengan transaksi DO ini
            // RiwayatHutang::where('transaksi_do_id', $transaksiDo->id)->delete();

            // 4. Hapus laporan
            LaporanKeuangan::where('transaksi_do_id', $transaksiDo->id)->delete();

            DB::commit();

            // Kirim notifikasi
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

    /**
     * Handle the TransaksiDo "restored" event.
     */
    public function restored(TransaksiDo $transaksiDo)
    {
        // Clear cache menggunakan CacheService
        CacheService::clearTransaksiCache($transaksiDo->penjual_id);
    }

    /**
     * Handle the TransaksiDo "force deleted" event.
     */
    public function forceDeleted(TransaksiDo $transaksiDo)
    {
        // Clear cache menggunakan CacheService
        CacheService::clearTransaksiCache($transaksiDo->penjual_id);
    }

    /**
     * Create laporan keuangan dengan validasi duplikasi
     */
    private function createLaporanKeuangan(array $data): ?LaporanKeuangan
    {
        try {
            // Validasi data wajib
            $requiredFields = [
                'tanggal',
                'jenis',
                'tipe_transaksi',
                'nominal',
                'saldo_sebelum',
                'saldo_sesudah',
                'keterangan'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Field {$field} wajib diisi pada laporan keuangan");
                }
            }

            // Cek duplikasi transaksi
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
    private function checkDuplikasiTransaksi(int $transaksiDoId, ?string $kategori, float $nominal): bool
    {
        $query = LaporanKeuangan::where('transaksi_do_id', $transaksiDoId)
            ->where('nominal', $nominal);

        if ($kategori) {
            $query->where('kategori_do', $kategori);
        }

        return $query->exists();
    }

    /**
     * Send notification berhasil
     */
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

    /**
     * Send notification hapus/batal
     */
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

        $message .= "\nSemua transaksi keuangan terkait telah dibatalkan dan saldo dikembalikan.";

        Notification::make()
            ->title('Transaksi DO Dibatalkan')
            ->body($message)
            ->warning()
            ->send();
    }

    /**
     * Build notification message
     */
    private function buildNotificationMessage(TransaksiDo $transaksiDo, $totalPemasukan, $totalPengeluaran, $selisih): string
    {
        $message = "DO #{$transaksiDo->nomor}\n\n";
        $message .= "Detail Transaksi:\n";
        $message .= "- Tonase: " . number_format($transaksiDo->tonase, 0, ',', '.') . " Kg\n";
        $message .= "- Total DO: Rp " . number_format($transaksiDo->total, 0, ',', '.') . "\n\n";

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

        return $message;
    }
}
