<?php
// app/Observers/OperasionalObserver.php

namespace App\Observers;

use App\Models\Operasional;
use App\Models\Penjual;
use App\Models\Pekerja;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class OperasionalObserver
{
    public function created(Operasional $operasional): void
    {
        // Logging untuk debugging
        \Log::info('Operasional Created:', [
            'operasional' => $operasional->operasional,
            'kategori' => $operasional->kategori?->nama,
            'tipe_nama' => $operasional->tipe_nama,
            'nominal' => $operasional->nominal
        ]);

        if (
            $operasional->operasional === 'pengeluaran' &&
            $operasional->kategori?->nama === 'Pinjaman'
        ) {
            $this->updateHutang($operasional);
        }
    }

    public function updated(Operasional $operasional): void
    {
        // Logging untuk debugging
        \Log::info('Operasional Updated:', [
            'changes' => $operasional->getDirty(),
            'original' => $operasional->getOriginal()
        ]);

        if (
            $operasional->operasional === 'pengeluaran' &&
            $operasional->kategori?->nama === 'Pinjaman'
        ) {

            // Rollback hutang lama jika ada perubahan
            if ($operasional->isDirty(['nominal', 'tipe_nama'])) {
                $this->rollbackHutang($operasional);
            }

            // Update dengan hutang baru
            $this->updateHutang($operasional);
        }
    }

    public function deleted(Operasional $operasional): void
    {
        if (
            $operasional->operasional === 'pengeluaran' &&
            $operasional->kategori?->nama === 'Pinjaman'
        ) {
            $this->rollbackHutang($operasional);
        }
    }

    private function updateHutang(Operasional $operasional): void
    {
        try {
            DB::beginTransaction();

            switch ($operasional->tipe_nama) {
                case 'penjual':
                    if ($operasional->penjual_id) {
                        $penjual = Penjual::find($operasional->penjual_id);
                        if ($penjual) {
                            // Tambah hutang yang baru
                            $penjual->hutang = $penjual->hutang + $operasional->nominal;
                            $penjual->save();

                            \Log::info('Hutang Penjual Updated:', [
                                'penjual_id' => $penjual->id,
                                'hutang_lama' => $penjual->getOriginal('hutang'),
                                'hutang_baru' => $penjual->hutang,
                                'nominal' => $operasional->nominal
                            ]);
                        }
                    }
                    break;

                case 'pekerja':
                    if ($operasional->pekerja_id) {
                        $pekerja = Pekerja::find($operasional->pekerja_id);
                        if ($pekerja) {
                            // Tambah hutang yang baru
                            $pekerja->hutang = $pekerja->hutang + $operasional->nominal;
                            $pekerja->save();

                            \Log::info('Hutang Pekerja Updated:', [
                                'pekerja_id' => $pekerja->id,
                                'hutang_lama' => $pekerja->getOriginal('hutang'),
                                'hutang_baru' => $pekerja->hutang,
                                'nominal' => $operasional->nominal
                            ]);
                        }
                    }
                    break;
            }

            DB::commit();

            // Tampilkan notifikasi sukses
            Notification::make()
                ->title('Hutang berhasil diperbarui')
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
        try {
            DB::beginTransaction();

            switch ($operasional->tipe_nama) {
                case 'penjual':
                    if ($operasional->penjual_id) {
                        $penjual = Penjual::find($operasional->penjual_id);
                        if ($penjual) {
                            // Kurangi hutang
                            $penjual->hutang = max(0, $penjual->hutang - $operasional->nominal);
                            $penjual->save();

                            \Log::info('Hutang Penjual Rolled Back:', [
                                'penjual_id' => $penjual->id,
                                'hutang_lama' => $penjual->getOriginal('hutang'),
                                'hutang_baru' => $penjual->hutang,
                                'nominal' => $operasional->nominal
                            ]);
                        }
                    }
                    break;

                case 'pekerja':
                    if ($operasional->pekerja_id) {
                        $pekerja = Pekerja::find($operasional->pekerja_id);
                        if ($pekerja) {
                            // Kurangi hutang
                            $pekerja->hutang = max(0, $pekerja->hutang - $operasional->nominal);
                            $pekerja->save();

                            \Log::info('Hutang Pekerja Rolled Back:', [
                                'pekerja_id' => $pekerja->id,
                                'hutang_lama' => $pekerja->getOriginal('hutang'),
                                'hutang_baru' => $pekerja->hutang,
                                'nominal' => $operasional->nominal
                            ]);
                        }
                    }
                    break;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error Rolling Back Hutang:', [
                'error' => $e->getMessage(),
                'operasional' => $operasional->toArray()
            ]);

            throw $e;
        }
    }
}
