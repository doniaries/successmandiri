<?php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\Penjual;
use App\Models\LaporanKeuangan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class OperasionalObserver
{
    public function created(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Get saldo terkini
            $saldoSekarang = Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->value('saldo') ?? 0;

            // Catat ke laporan keuangan
            LaporanKeuangan::create([
                'tanggal' => $operasional->tanggal,
                'jenis' => $operasional->operasional === 'pemasukan' ? 'masuk' : 'keluar',
                'tipe_transaksi' => 'operasional',
                'kategori_operasional_id' => $operasional->kategori_id,
                'keterangan' => $this->generateKeterangan($operasional),
                'nominal' => $operasional->nominal,
                'saldo_sebelum' => $saldoSekarang,
                'saldo_sesudah' => $operasional->operasional === 'pemasukan' ?
                    $saldoSekarang + $operasional->nominal :
                    $saldoSekarang - $operasional->nominal,
                'operasional_id' => $operasional->id,
                'created_by' => auth()->id()
            ]);

            // Update saldo perusahaan
            Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->{$operasional->operasional === 'pemasukan' ? 'increment' : 'decrement'}('saldo', $operasional->nominal);

            // Proses hutang
            $this->processHutang($operasional);

            DB::commit();

            // Show appropriate notification
            $this->showTransactionNotification($operasional, 'created');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logAndNotifyError('created', $e, $operasional);
            throw $e;
        }
    }

    public function updated(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Get saldo before changes
            $saldoSekarang = Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->value('saldo') ?? 0;

            // First restore previous saldo
            $oldRecord = LaporanKeuangan::where('operasional_id', $operasional->id)->first();
            if ($oldRecord) {
                Perusahaan::query()
                    ->where('id', auth()->user()->perusahaan_id)
                    ->{$oldRecord->jenis === 'masuk' ? 'decrement' : 'increment'}('saldo', $oldRecord->nominal);

                $oldRecord->delete();
            }

            // Create new record
            LaporanKeuangan::create([
                'tanggal' => $operasional->tanggal,
                'jenis' => $operasional->operasional === 'pemasukan' ? 'masuk' : 'keluar',
                'tipe_transaksi' => 'operasional',
                'kategori_operasional_id' => $operasional->kategori_id,
                'keterangan' => $this->generateKeterangan($operasional),
                'nominal' => $operasional->nominal,
                'saldo_sebelum' => $saldoSekarang,
                'saldo_sesudah' => $operasional->operasional === 'pemasukan' ?
                    $saldoSekarang + $operasional->nominal :
                    $saldoSekarang - $operasional->nominal,
                'operasional_id' => $operasional->id,
                'created_by' => auth()->id()
            ]);

            // Update new saldo
            Perusahaan::query()
                ->where('id', auth()->user()->perusahaan_id)
                ->{$operasional->operasional === 'pemasukan' ? 'increment' : 'decrement'}('saldo', $operasional->nominal);

            // Process hutang changes if any
            if ($operasional->isDirty(['nominal', 'tipe_nama'])) {
                $this->rollbackHutang($operasional);
                $this->processHutang($operasional);
            }

            DB::commit();

            $this->showTransactionNotification($operasional, 'updated');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logAndNotifyError('updated', $e, $operasional);
            throw $e;
        }
    }

    public function deleted(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // Get record from laporan keuangan
            $record = LaporanKeuangan::where('operasional_id', $operasional->id)->first();

            if ($record) {
                // Restore saldo
                Perusahaan::query()
                    ->where('id', auth()->user()->perusahaan_id)
                    ->{$record->jenis === 'masuk' ? 'decrement' : 'increment'}('saldo', $record->nominal);

                // Delete record
                $record->delete();
            }

            // Rollback hutang if applicable
            $this->rollbackHutang($operasional);

            DB::commit();

            $this->showTransactionNotification($operasional, 'deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logAndNotifyError('deleted', $e, $operasional);
            throw $e;
        }
    }

    private function processHutang(Operasional $operasional): void
    {
        if ($operasional->operasional === 'pengeluaran' && $operasional->kategori?->nama === 'Pinjaman') {
            if ($operasional->tipe_nama === 'penjual' && $operasional->penjual_id) {
                Penjual::where('id', $operasional->penjual_id)
                    ->increment('hutang', $operasional->nominal);
            }
        }

        if ($operasional->operasional === 'pemasukan' && $operasional->kategori?->nama === 'Bayar Hutang') {
            if ($operasional->tipe_nama === 'penjual' && $operasional->penjual_id) {
                Penjual::where('id', $operasional->penjual_id)
                    ->decrement('hutang', $operasional->nominal);
            }
        }
    }

    private function rollbackHutang(Operasional $operasional): void
    {
        if ($operasional->operasional === 'pengeluaran' && $operasional->kategori?->nama === 'Pinjaman') {
            if ($operasional->tipe_nama === 'penjual' && $operasional->penjual_id) {
                Penjual::where('id', $operasional->penjual_id)
                    ->decrement('hutang', $operasional->nominal);
            }
        }

        if ($operasional->operasional === 'pemasukan' && $operasional->kategori?->nama === 'Bayar Hutang') {
            if ($operasional->tipe_nama === 'penjual' && $operasional->penjual_id) {
                Penjual::where('id', $operasional->penjual_id)
                    ->increment('hutang', $operasional->nominal);
            }
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

        return "({$kategori}) {$nama}{$keterangan}";
    }

    private function showTransactionNotification(Operasional $operasional, string $action): void
    {
        $nominal = number_format($operasional->nominal, 0, ',', '.');

        if (in_array($operasional->kategori?->nama, ['Pinjaman', 'Bayar Hutang'])) {
            $title = match ($operasional->kategori?->nama) {
                'Pinjaman' => 'Pinjaman Berhasil ' . ($action === 'deleted' ? 'Dihapus' : ($action === 'updated' ? 'Diupdate' : 'Dicatat')),
                'Bayar Hutang' => 'Pembayaran Hutang Berhasil ' . ($action === 'deleted' ? 'Dihapus' : ($action === 'updated' ? 'Diupdate' : 'Dicatat')),
                default => 'Transaksi Berhasil'
            };

            $body = match ($operasional->kategori?->nama) {
                'Pinjaman' => "Pinjaman sebesar Rp {$nominal} telah " . ($action === 'deleted' ? 'dihapus' : ($action === 'updated' ? 'diupdate' : 'dicatat')),
                'Bayar Hutang' => "Pembayaran hutang sebesar Rp {$nominal} telah " . ($action === 'deleted' ? 'dihapus' : ($action === 'updated' ? 'diupdate' : 'dicatat')),
                default => "Transaksi sebesar Rp {$nominal} telah " . ($action === 'deleted' ? 'dihapus' : ($action === 'updated' ? 'diupdate' : 'dicatat'))
            };

            $message = match ($operasional->kategori?->nama) {
                'Pinjaman' => "Hutang {$operasional->penjual->nama} bertambah",
                'Bayar Hutang' => "Hutang {$operasional->penjual->nama} berkurang",
                default => $operasional->keterangan
            };
        } else {
            $title = 'Transaksi Operasional ' . ($action === 'deleted' ? 'Dihapus' : ($action === 'updated' ? 'Diupdate' : 'Berhasil'));
            $body = ($operasional->operasional === 'pemasukan' ? "Pemasukan" : "Pengeluaran") .
                " sebesar Rp {$nominal} telah " .
                ($action === 'deleted' ? 'dihapus' : ($action === 'updated' ? 'diupdate' : 'dicatat'));
            $message = $operasional->keterangan;
        }

        $this->showNotification($title, $body, $message);
    }

    private function logAndNotifyError(string $action, \Exception $e, Operasional $operasional): void
    {
        \Log::error("Error {$action} Operasional:", [
            'error' => $e->getMessage(),
            'operasional' => $operasional->toArray()
        ]);

        $this->showNotification(
            'Error!',
            "Terjadi kesalahan saat {$action} transaksi",
            $e->getMessage(),
            'danger'
        );
    }

    private function showNotification(string $title, string $body, string $message = '', string $type = 'success'): void
    {
        Notification::make()
            ->title($title)
            ->{$type}()
            ->body($body)
            ->when(
                $message !== '',
                fn($notification) => $notification->message($message)
            )
            ->persistent()
            ->send();
    }
}
