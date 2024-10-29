<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use Filament\Actions;
use App\Models\Penjual;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaksiDo extends EditRecord
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Hitung perubahan pembayaran hutang
        $originalBayarHutang = $record->getOriginal('bayar_hutang') ?? 0;
        $bayarHutangBaru = $record->bayar_hutang;
        $selisihBayar = $bayarHutangBaru - $originalBayarHutang;

        // Jika ada perubahan pembayaran hutang
        if ($selisihBayar != 0) {
            $penjual = Penjual::find($record->penjual_id);
            if ($penjual) {
                // Update hutang penjual
                $penjual->hutang = max(0, $penjual->hutang - $selisihBayar);
                $penjual->save();

                // Notifikasi sukses
                Notification::make()
                    ->title('Hutang penjual berhasil diupdate')
                    ->success()
                    ->send();
            }
        }
    }
}
