<?php

namespace App\Filament\Resources\PinjamResource\Pages;

use App\Filament\Resources\PinjamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Pekerja;
use App\Models\Penjual;
use Exception;
use Filament\Notifications\Notification;

class EditPinjam extends EditRecord
{
    protected static string $resource = PinjamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Menggunakan method bawaan Filament 3
    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        try {
            DB::beginTransaction();

            $record = $this->getRecord();

            if ($record->nominal != $data['nominal']) {
                $peminjam = match ($data['kategori_peminjam']) {
                    'Pekerja' => Pekerja::find($data['peminjam_id']),
                    'Penjual' => Penjual::find($data['peminjam_id']),
                    default => null
                };

                if (!$peminjam) {
                    throw new Exception('Data peminjam tidak ditemukan.');
                }

                $peminjam->hutang = ($peminjam->hutang - $record->nominal) + $data['nominal'];
                $peminjam->save();
            }

            DB::commit();

            return $data;
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal mengupdate pinjaman')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->persistent()
                ->send();

            throw $e;
        }
    }

    // Override afterSave untuk notifikasi sukses
    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Berhasil mengupdate pinjaman')
            ->body('Data pinjaman berhasil diperbarui.')
            ->send();
    }
}