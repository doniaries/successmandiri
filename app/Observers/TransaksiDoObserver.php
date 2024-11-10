<?php

namespace App\Observers;

use App\Models\Keuangan;
use App\Models\TransaksiDo;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class TransaksiDoObserver
{
    public function created(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            $perusahaan = Perusahaan::first();

            if (!$perusahaan) {
                throw new \Exception('Data perusahaan tidak ditemukan');
            }

            // 1. Catat total DO sebagai pengeluaran dan kurangi saldo perusahaan
            if ($transaksiDo->sisa_bayar > 0) {
                if ($perusahaan->saldo < $transaksiDo->sisa_bayar) {
                    throw new \Exception('Saldo perusahaan tidak mencukupi untuk pembayaran DO');
                }

                Keuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis_transaksi' => 'Keluar',
                    'kategori' => 'Sisa Bayar',
                    'sumber' => 'Penjual',
                    'jumlah' => $transaksiDo->sisa_bayar,
                    'keterangan' => "Sisa Bayar DO #{$transaksiDo->nomor}"
                ]);

                // Kurangi saldo perusahaan
                $perusahaan->decrement('saldo', $transaksiDo->sisa_bayar);

                \Log::info('Saldo perusahaan berkurang:', [
                    'nomor_do' => $transaksiDo->nomor,
                    'pengurangan' => $transaksiDo->sisa_bayar,
                    'saldo_akhir' => $perusahaan->saldo
                ]);
            }

            // 2. Jika ada pembayaran hutang, tambah saldo perusahaan
            if ($transaksiDo->bayar_hutang > 0) {
                Keuangan::create([
                    'tanggal' => $transaksiDo->tanggal,
                    'jenis_transaksi' => 'Masuk',
                    'kategori' => 'Bayar Hutang',
                    'sumber' => 'Penjual',
                    'jumlah' => $transaksiDo->bayar_hutang,
                    'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}"
                ]);

                // Tambah saldo perusahaan
                $perusahaan->increment('saldo', $transaksiDo->bayar_hutang);

                \Log::info('Saldo perusahaan bertambah:', [
                    'nomor_do' => $transaksiDo->nomor,
                    'penambahan' => $transaksiDo->bayar_hutang,
                    'saldo_akhir' => $perusahaan->saldo
                ]);

                // Update hutang penjual
                $penjual = $transaksiDo->penjual;
                if ($penjual) {
                    $penjual->decrement('hutang', $transaksiDo->bayar_hutang);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error recording transaction: ' . $e->getMessage(), [
                'transaksi_do' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }

    public function deleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            $perusahaan = Perusahaan::first();

            if (!$perusahaan) {
                throw new \Exception('Data perusahaan tidak ditemukan');
            }

            // 1. Kembalikan saldo yang sudah dikurangi
            if ($transaksiDo->sisa_bayar > 0) {
                $perusahaan->increment('saldo', $transaksiDo->sisa_bayar);

                \Log::info('Saldo perusahaan dikembalikan:', [
                    'nomor_do' => $transaksiDo->nomor,
                    'penambahan' => $transaksiDo->sisa_bayar,
                    'saldo_akhir' => $perusahaan->saldo
                ]);
            }

            // 2. Kurangi saldo dari pembayaran hutang yang dibatalkan
            if ($transaksiDo->bayar_hutang > 0) {
                $perusahaan->decrement('saldo', $transaksiDo->bayar_hutang);

                \Log::info('Saldo perusahaan dikurangi:', [
                    'nomor_do' => $transaksiDo->nomor,
                    'pengurangan' => $transaksiDo->bayar_hutang,
                    'saldo_akhir' => $perusahaan->saldo
                ]);

                // Kembalikan hutang penjual
                $penjual = $transaksiDo->penjual;
                if ($penjual) {
                    $penjual->increment('hutang', $transaksiDo->bayar_hutang);
                }
            }

            // 3. Hapus semua transaksi keuangan terkait
            Keuangan::where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->delete();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi DO dan data terkait berhasil dihapus')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error Deleting DO:', [
                'error' => $e->getMessage(),
                'transaksi' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }

    public function restored(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            $perusahaan = Perusahaan::first();

            if (!$perusahaan) {
                throw new \Exception('Data perusahaan tidak ditemukan');
            }

            // 1. Restore pengurangan saldo
            if ($transaksiDo->sisa_bayar > 0) {
                $perusahaan->decrement('saldo', $transaksiDo->sisa_bayar);
            }

            // 2. Restore penambahan saldo dari pembayaran hutang
            if ($transaksiDo->bayar_hutang > 0) {
                $perusahaan->increment('saldo', $transaksiDo->bayar_hutang);

                // Restore pengurangan hutang penjual
                $penjual = $transaksiDo->penjual;
                if ($penjual) {
                    $penjual->decrement('hutang', $transaksiDo->bayar_hutang);
                }
            }

            // 3. Restore transaksi keuangan
            Keuangan::onlyTrashed()
                ->where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->restore();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi DO dan data terkait berhasil dipulihkan')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error Restoring DO:', [
                'error' => $e->getMessage(),
                'transaksi' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }

    public function forceDeleted(TransaksiDo $transaksiDo)
    {
        try {
            DB::beginTransaction();

            $perusahaan = Perusahaan::first();

            if (!$perusahaan) {
                throw new \Exception('Data perusahaan tidak ditemukan');
            }

            // Proses sama seperti soft delete
            if ($transaksiDo->sisa_bayar > 0) {
                $perusahaan->increment('saldo', $transaksiDo->sisa_bayar);
            }

            if ($transaksiDo->bayar_hutang > 0) {
                $perusahaan->decrement('saldo', $transaksiDo->bayar_hutang);

                $penjual = $transaksiDo->penjual;
                if ($penjual) {
                    $penjual->increment('hutang', $transaksiDo->bayar_hutang);
                }
            }

            // Hapus permanen transaksi keuangan
            Keuangan::withTrashed()
                ->where('keterangan', 'like', "%DO #{$transaksiDo->nomor}%")
                ->forceDelete();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi DO dan data terkait berhasil dihapus permanen')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error Force Deleting DO:', [
                'error' => $e->getMessage(),
                'transaksi' => $transaksiDo->toArray()
            ]);
            throw $e;
        }
    }
}
