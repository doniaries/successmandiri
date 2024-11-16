<?php

namespace App\Observers;

use App\Models\{Operasional, Penjual, Perusahaan};
use Illuminate\Support\Facades\{DB, Log};
use Filament\Notifications\Notification;

class OperasionalObserver
{
    protected $laporanObserver;

    public function __construct(LaporanKeuanganObserver $laporanObserver)
    {
        $this->laporanObserver = $laporanObserver;
    }

    public function created(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            // 1. Catat ke laporan keuangan melalui observer
            $this->laporanObserver->handleOperasional($operasional);

            // 2. Proses perubahan hutang jika ada
            $this->processHutang($operasional);

            DB::commit();

            // 3. Tampilkan notifikasi
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

            // 1. Update laporan keuangan melalui observer
            $this->laporanObserver->handleOperasional($operasional);

            // 2. Proses perubahan hutang jika ada
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

            // 1. Rollback hutang jika ada
            $this->rollbackHutang($operasional);

            DB::commit();

            $this->showTransactionNotification($operasional, 'deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logAndNotifyError('deleted', $e, $operasional);
            throw $e;
        }
    }

    // Helper Methods
    private function processHutang(Operasional $operasional): void
    {
        if ($operasional->tipe_nama !== 'penjual' || !$operasional->penjual_id) {
            return;
        }

        if ($operasional->kategori?->nama === 'Pinjaman' && $operasional->operasional === 'pengeluaran') {
            Penjual::where('id', $operasional->penjual_id)
                ->increment('hutang', $operasional->nominal);
        }

        if ($operasional->kategori?->nama === 'Bayar Hutang' && $operasional->operasional === 'pemasukan') {
            Penjual::where('id', $operasional->penjual_id)
                ->decrement('hutang', $operasional->nominal);
        }
    }

    private function rollbackHutang(Operasional $operasional): void
    {
        if ($operasional->tipe_nama !== 'penjual' || !$operasional->penjual_id) {
            return;
        }

        if ($operasional->kategori?->nama === 'Pinjaman' && $operasional->operasional === 'pengeluaran') {
            Penjual::where('id', $operasional->penjual_id)
                ->decrement('hutang', $operasional->nominal);
        }

        if ($operasional->kategori?->nama === 'Bayar Hutang' && $operasional->operasional === 'pemasukan') {
            Penjual::where('id', $operasional->penjual_id)
                ->increment('hutang', $operasional->nominal);
        }
    }

    private function showTransactionNotification(Operasional $operasional, string $action): void
    {
        $nominal = number_format($operasional->nominal, 0, ',', '.');
        $isHutangRelated = in_array($operasional->kategori?->nama, ['Pinjaman', 'Bayar Hutang']);

        $title = $isHutangRelated
            ? $this->getHutangNotificationTitle($operasional, $action)
            : "Transaksi Operasional " . $this->getActionText($action);

        $body = $isHutangRelated
            ? $this->getHutangNotificationBody($operasional, $action, $nominal)
            : $this->getOperasionalNotificationBody($operasional, $action, $nominal);

        $message = $isHutangRelated
            ? $this->getHutangNotificationMessage($operasional)
            : $operasional->keterangan;

        $this->showNotification($title, $body, $message);
    }

    private function getActionText(string $action): string
    {
        return match ($action) {
            'deleted' => 'Dihapus',
            'updated' => 'Diupdate',
            default => 'Berhasil'
        };
    }

    private function getHutangNotificationTitle(Operasional $operasional, string $action): string
    {
        $type = $operasional->kategori?->nama === 'Pinjaman' ? 'Pinjaman' : 'Pembayaran Hutang';
        return "{$type} Berhasil " . $this->getActionText($action);
    }

    private function getHutangNotificationBody(Operasional $operasional, string $action, string $nominal): string
    {
        $type = $operasional->kategori?->nama === 'Pinjaman' ? 'Pinjaman' : 'Pembayaran hutang';
        $actionText = $this->getActionText($action);
        return "{$type} sebesar Rp {$nominal} telah {$actionText}";
    }

    private function getHutangNotificationMessage(Operasional $operasional): string
    {
        $isIncrement = $operasional->kategori?->nama === 'Pinjaman';
        return "Hutang {$operasional->penjual->nama} " . ($isIncrement ? 'bertambah' : 'berkurang');
    }

    private function showNotification(string $title, string $body, string $message = '', string $type = 'success'): void
    {
        $notification = Notification::make()
            ->title($title)
            ->{$type}()
            ->body($body)
            ->persistent();

        if ($message) {
            $notification->message($message);
        }

        $notification->send();
    }

    private function logAndNotifyError(string $action, \Exception $e, Operasional $operasional): void
    {
        Log::error("Error {$action} Operasional:", [
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
}
