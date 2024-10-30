<?php

namespace App\Filament\Resources\PinjamResource\Pages;

use App\Filament\Resources\PinjamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Pekerja;
use App\Models\Penjual;
use Exception;
use Filament\Notifications\Notification;

class CreatePinjam extends CreateRecord
{
    protected static string $resource = PinjamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Menggunakan method bawaan Filament 3
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            DB::beginTransaction();

            $peminjam = match ($data['kategori_peminjam']) {
                'Pekerja' => Pekerja::find($data['peminjam_id']),
                'Penjual' => Penjual::find($data['peminjam_id']),
                default => null
            };

            if (!$peminjam) {
                throw new Exception('Data peminjam tidak ditemukan.');
            }

            $peminjam->hutang += $data['nominal'];
            $peminjam->save();

            DB::commit();

            return $data;
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal membuat pinjaman')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->persistent()
                ->send();

            throw $e;
        }
    }

    // Override afterCreate untuk notifikasi sukses
    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Berhasil membuat pinjaman')
            ->body('Data pinjaman berhasil disimpan dan hutang peminjam telah diperbarui.')
            ->send();
    }
}