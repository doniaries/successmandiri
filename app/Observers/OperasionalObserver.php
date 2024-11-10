<?php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\Penjual;
use App\Models\Keuangan;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class OperasionalObserver
{
    public function created(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Catat ke tabel keuangan
            $this->catatKeuangan($operasional);

            // Proses hutang jika diperlukan
            if (
                $operasional->operasional === 'pengeluaran' &&
                $operasional->kategori?->nama === 'Pinjaman'
            ) {
                $this->updateHutang($operasional, 'tambah');
            }

            if (
                $operasional->operasional === 'pemasukan' &&
                $operasional->kategori?->nama === 'Bayar Hutang'
            ) {
                $this->updateHutang($operasional, 'kurang');
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi berhasil dicatat')
                ->body('Data telah dicatat di Operasional dan Keuangan')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in OperasionalObserver:', [
                'error' => $e->getMessage(),
                'operasional' => $operasional->toArray()
            ]);

            throw $e;
        }
    }

    public function updated(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Update catatan keuangan
            $this->updateCatatanKeuangan($operasional);

            if ($operasional->isDirty(['nominal', 'tipe_nama'])) {
                if (
                    $operasional->operasional === 'pengeluaran' &&
                    $operasional->kategori?->nama === 'Pinjaman'
                ) {
                    $this->rollbackHutang($operasional);
                    $this->updateHutang($operasional, 'tambah');
                }

                if (
                    $operasional->operasional === 'pemasukan' &&
                    $operasional->kategori?->nama === 'Bayar Hutang'
                ) {
                    $this->rollbackHutang($operasional);
                    $this->updateHutang($operasional, 'kurang');
                }
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi berhasil diperbarui')
                ->body('Data telah diperbarui di Operasional dan Keuangan')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating Operasional:', [
                'error' => $e->getMessage(),
                'operasional' => $operasional->toArray()
            ]);

            throw $e;
        }
    }

    public function deleted(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Hapus catatan keuangan terkait
            Keuangan::where('keterangan', 'LIKE', "%Operasional #{$operasional->id}%")->delete();

            // Proses hutang jika diperlukan
            if (
                $operasional->operasional === 'pengeluaran' &&
                $operasional->kategori?->nama === 'Pinjaman'
            ) {
                $this->updateHutang($operasional, 'kurang');
            }

            if (
                $operasional->operasional === 'pemasukan' &&
                $operasional->kategori?->nama === 'Bayar Hutang'
            ) {
                $this->updateHutang($operasional, 'tambah');
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Transaksi berhasil dihapus')
                ->body('Data telah dihapus dari Operasional dan Keuangan')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting Operasional:', [
                'error' => $e->getMessage(),
                'operasional' => $operasional->toArray()
            ]);

            throw $e;
        }
    }

    private function catatKeuangan(Operasional $operasional): void
    {
        Keuangan::create([
            'tanggal' => $operasional->tanggal,
            'keterangan' => $this->generateKeterangan($operasional),
            'jenis_transaksi' => $operasional->operasional === 'pemasukan' ? 'Masuk' : 'Keluar',
            'jumlah' => $operasional->nominal,
            'kategori' => $operasional->kategori?->nama ?? 'Lain-lain',
            'sumber' => $operasional->is_from_transaksi ? 'Transaksi DO' : 'Operasional',
        ]);
    }

    private function updateCatatanKeuangan(Operasional $operasional): void
    {
        $keuangan = Keuangan::where('keterangan', 'LIKE', "%Operasional #{$operasional->id}%")->first();

        if ($keuangan) {
            $keuangan->update([
                'tanggal' => $operasional->tanggal,
                'keterangan' => $this->generateKeterangan($operasional),
                'jenis_transaksi' => $operasional->operasional === 'pemasukan' ? 'Masuk' : 'Keluar',
                'jumlah' => $operasional->nominal,
                'kategori' => $operasional->kategori?->nama ?? 'Lain-lain',
                'sumber' => $operasional->is_from_transaksi ? 'Transaksi DO' : 'Operasional',
            ]);
        } else {
            $this->catatKeuangan($operasional);
        }
    }

    private function generateKeterangan(Operasional $operasional): string
    {
        $nama = match ($operasional->tipe_nama) {
            'penjual' => $operasional->penjual?->nama ?? '-',
            'pekerja' => $operasional->pekerja?->nama ?? '-',
            'user' => $operasional->user?->name ?? '-',
            default => '-'
        };

        $kategori = $operasional->kategori?->nama ?? 'Tanpa Kategori';
        $keterangan = $operasional->keterangan ? " - {$operasional->keterangan}" : '';
        $fromDO = $operasional->is_from_transaksi ? ' (Via Transaksi DO)' : '';

        return "Operasional #{$operasional->id} - {$kategori} - {$nama}{$keterangan}{$fromDO}";
    }

    private function updateHutang(Operasional $operasional, string $action): void
    {
        // Kode updateHutang yang sudah ada tetap sama
    }

    private function rollbackHutang(Operasional $operasional): void
    {
        // Kode rollbackHutang yang sudah ada tetap sama
    }
}
