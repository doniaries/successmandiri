<?php

namespace App\Observers;

use App\Models\{TransaksiDo, LaporanKeuangan, Perusahaan, Penjual, RiwayatHutang};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{DB, Log};

class TransaksiDoObserver
{
    public function creating(TransaksiDo $transaksiDo)
    {
        try {
            // 1. Validasi data wajib
            if (!$transaksiDo->tanggal) throw new \Exception("Tanggal wajib diisi");
            if (!$transaksiDo->penjual_id) throw new \Exception("Penjual wajib dipilih");
            if ($transaksiDo->tonase <= 0) throw new \Exception("Tonase harus lebih dari 0");
            if ($transaksiDo->harga_satuan <= 0) throw new \Exception("Harga satuan harus lebih dari 0");

            // 2. Simpan dan validasi hutang
            if ($transaksiDo->penjual_id) {
                $penjual = Penjual::find($transaksiDo->penjual_id);
                if (!$penjual) throw new \Exception("Penjual tidak ditemukan");

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

            // 4. Validasi saldo perusahaan
            $perusahaan = Perusahaan::first();
            if (!$perusahaan) throw new \Exception("Data perusahaan tidak ditemukan");

            if ($transaksiDo->sisa_bayar > $perusahaan->saldo) {
                throw new \Exception(
                    "Saldo perusahaan tidak mencukupi untuk transaksi.\n" .
                        "Saldo saat ini: Rp " . number_format($perusahaan->saldo, 0, ',', '.') . "\n" .
                        "Dibutuhkan: Rp " . number_format($transaksiDo->sisa_bayar, 0, ',', '.')
                );
            }

            Log::info('Data DO Siap Disimpan:', [
                'nomor' => $transaksiDo->nomor,
                'penjual' => $penjual->nama,
                'total' => $transaksiDo->total,
                'pemasukan' => $totalPemasukan,
                'sisa_bayar' => $transaksiDo->sisa_bayar,
                'hutang_awal' => $transaksiDo->hutang_awal,
                'pembayaran_hutang' => $transaksiDo->pembayaran_hutang,
                'sisa_hutang' => $transaksiDo->sisa_hutang_penjual
            ]);
        } catch (\Exception $e) {
            Log::error('Error Validasi TransaksiDO:', [
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

            $perusahaan = Perusahaan::first();
            if (!$perusahaan) throw new \Exception("Data perusahaan tidak ditemukan");

            $saldoBerjalan = $perusahaan->saldo;

            // 1. Proses Pemasukan
            $komponenPemasukan = [
                'upah_bongkar' => [
                    'nominal' => $transaksiDo->upah_bongkar,
                    'keterangan' => "Pemasukan Upah Bongkar"
                ],
                'biaya_lain' => [
                    'nominal' => $transaksiDo->biaya_lain,
                    'keterangan' => "Pemasukan Biaya Lain"
                ]
            ];

            foreach ($komponenPemasukan as $kategori => $data) {
                if ($data['nominal'] > 0) {
                    // Catat laporan keuangan
                    $laporan = $this->createLaporanKeuangan([
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
                        'nama_penjual' => $transaksiDo->penjual?->nama
                    ]);

                    // Update saldo
                    $perusahaan->increment('saldo', $data['nominal']);
                    $saldoBerjalan += $data['nominal'];

                    Log::info("Pemasukan {$kategori} tercatat:", [
                        'nominal' => $data['nominal'],
                        'laporan_id' => $laporan->id
                    ]);
                }
            }

            // 2. Proses Pembayaran Hutang
            if ($transaksiDo->pembayaran_hutang > 0 && $transaksiDo->penjual) {
                // Catat pembayaran hutang di laporan keuangan
                $laporan = $this->createLaporanKeuangan([
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
                    'nama_penjual' => $transaksiDo->penjual->nama
                ]);

                // Update saldo perusahaan
                $perusahaan->increment('saldo', $transaksiDo->pembayaran_hutang);
                $saldoBerjalan += $transaksiDo->pembayaran_hutang;

                // Update hutang penjual
                $transaksiDo->penjual->decrement('hutang', $transaksiDo->pembayaran_hutang);

                // Catat riwayat hutang
                $riwayat = RiwayatHutang::create([
                    'tipe_entitas' => 'penjual',
                    'entitas_id' => $transaksiDo->penjual_id,
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'jenis' => 'pengurangan',
                    'hutang_sebelum' => $transaksiDo->hutang_awal,
                    'hutang_sesudah' => $transaksiDo->sisa_hutang_penjual,
                    'keterangan' => "Pembayaran hutang via DO #{$transaksiDo->nomor}",
                    'transaksi_do_id' => $transaksiDo->id
                ]);

                Log::info('Pembayaran Hutang tercatat:', [
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'laporan_id' => $laporan->id,
                    'riwayat_id' => $riwayat->id
                ]);
            }

            // 3. Proses Pembayaran Sisa DO
            if ($transaksiDo->sisa_bayar > 0) {
                // Catat pembayaran DO
                $laporan = $this->createLaporanKeuangan([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis' => 'keluar',
                    'tipe_transaksi' => 'transaksi_do',
                    'kategori_do' => 'pembayaran_do',
                    'nominal' => $transaksiDo->sisa_bayar,
                    'keterangan' => "Pembayaran Sisa DO #{$transaksiDo->nomor}",
                    'saldo_sebelum' => $saldoBerjalan,
                    'saldo_sesudah' => $saldoBerjalan - $transaksiDo->sisa_bayar,
                    'transaksi_do_id' => $transaksiDo->id,
                    'nomor_transaksi' => $transaksiDo->nomor,
                    'nama_penjual' => $transaksiDo->penjual->nama
                ]);

                // Update saldo
                $perusahaan->decrement('saldo', $transaksiDo->sisa_bayar);

                Log::info('Pembayaran Sisa DO tercatat:', [
                    'nominal' => $transaksiDo->sisa_bayar,
                    'laporan_id' => $laporan->id
                ]);
            }

            DB::commit();

            // Kirim notifikasi sukses
            $this->sendSuccessNotification($transaksiDo);

            Log::info('TransaksiDO Berhasil:', [
                'nomor' => $transaksiDo->nomor,
                'saldo_akhir' => $perusahaan->fresh()->saldo
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Proses TransaksiDO:', [
                'error' => $e->getMessage(),
                'data' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

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

                // Catat di riwayat hutang
                $riwayat = RiwayatHutang::create([
                    'tipe_entitas' => 'penjual',
                    'entitas_id' => $transaksiDo->penjual_id,
                    'nominal' => $transaksiDo->pembayaran_hutang,
                    'jenis' => 'penambahan',
                    'hutang_sebelum' => $hutangSebelum,
                    'hutang_sesudah' => $hutangSesudah,
                    'keterangan' => "Pembatalan DO #{$transaksiDo->nomor}",
                    'transaksi_do_id' => $transaksiDo->id
                ]);

                $dataPembatalan['riwayat_hutang'] = [
                    'id' => $riwayat->id,
                    'hutang_sebelum' => $hutangSebelum,
                    'hutang_sesudah' => $hutangSesudah
                ];
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

    private function createLaporanKeuangan(array $data): LaporanKeuangan
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

        $message .= "\nSemua transaksi keuangan terkait telah dibatalkan dan saldo dikembalikan.";

        Notification::make()
            ->title('Transaksi DO Dibatalkan')
            ->body($message)
            ->warning()
            ->send();
    }
}
