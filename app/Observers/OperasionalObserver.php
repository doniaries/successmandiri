<?php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\Penjual;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class OperasionalObserver
{
    public function created(Operasional $operasional): void
    {
        \Log::info('Operasional Created:', [
            'operasional' => $operasional->operasional,
            'kategori' => $operasional->kategori?->nama,
            'tipe_nama' => $operasional->tipe_nama,
            'nominal' => $operasional->nominal
        ]);

        // Peminjaman - Tambah Hutang
        if (
            $operasional->operasional === 'pengeluaran' &&
            $operasional->kategori?->nama === 'Pinjaman'
        ) {
            $this->updateHutang($operasional, 'tambah');
        }

        // Pembayaran - Kurangi Hutang
        if (
            $operasional->operasional === 'pemasukan' &&
            $operasional->kategori?->nama === 'Bayar Hutang'
        ) {
            $this->updateHutang($operasional, 'kurang');
        }
    }

    public function updated(Operasional $operasional): void
    {
        \Log::info('Operasional Updated:', [
            'changes' => $operasional->getDirty(),
            'original' => $operasional->getOriginal()
        ]);

        if ($operasional->isDirty(['nominal', 'tipe_nama'])) {
            // Peminjaman
            if (
                $operasional->operasional === 'pengeluaran' &&
                $operasional->kategori?->nama === 'Pinjaman'
            ) {
                $this->rollbackHutang($operasional);
                $this->updateHutang($operasional, 'tambah');
            }

            // Pembayaran
            if (
                $operasional->operasional === 'pemasukan' &&
                $operasional->kategori?->nama === 'Bayar Hutang'
            ) {
                $this->rollbackHutang($operasional);
                $this->updateHutang($operasional, 'kurang');
            }
        }
    }

    public function deleted(Operasional $operasional): void
    {
        // Peminjaman dihapus - Kurangi Hutang
        if (
            $operasional->operasional === 'pengeluaran' &&
            $operasional->kategori?->nama === 'Pinjaman'
        ) {
            $this->updateHutang($operasional, 'kurang');
        }

        // Pembayaran dihapus - Tambah Hutang
        if (
            $operasional->operasional === 'pemasukan' &&
            $operasional->kategori?->nama === 'Bayar Hutang'
        ) {
            $this->updateHutang($operasional, 'tambah');
        }
    }

    private function updateHutang(Operasional $operasional, string $action): void
    {
        try {
            DB::beginTransaction();

            switch ($operasional->tipe_nama) {
                case 'penjual':
                    if ($operasional->penjual_id) {
                        $penjual = Penjual::find($operasional->penjual_id);
                        if ($penjual) {
                            $hutangLama = $penjual->hutang;

                            if ($action === 'tambah') {
                                $penjual->hutang += $operasional->nominal;
                            } else {
                                $penjual->hutang = max(0, $penjual->hutang - $operasional->nominal);
                            }

                            $penjual->save();

                            \Log::info('Hutang Penjual Updated:', [
                                'penjual_id' => $penjual->id,
                                'action' => $action,
                                'hutang_lama' => $hutangLama,
                                'hutang_baru' => $penjual->hutang,
                                'nominal' => $operasional->nominal
                            ]);
                        }
                    }
                    break;
            }

            DB::commit();

            $message = $action === 'tambah' ? 'ditambahkan' : 'dikurangi';
            Notification::make()
                ->title("Hutang berhasil {$message}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error Updating Hutang:', [
                'error' => $e->getMessage(),
                'operasional' => $operasional->toArray()
            ]);

            Notification::make()
                ->title('Error saat memperbarui hutang')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function rollbackHutang(Operasional $operasional): void
    {
        // Untuk rollback, kita lakukan kebalikan dari operasi asli
        $action = $operasional->operasional === 'pengeluaran' ? 'kurang' : 'tambah';
        $this->updateHutang($operasional, $action);
    }
}
