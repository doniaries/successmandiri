<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Models\Penjual;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTransaksiDo extends CreateRecord
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->bayar_hutang > 0) {
            $penjual = Penjual::find($record->penjual_id);
            if ($penjual) {
                // Kurangi hutang penjual
                $penjual->hutang = max(0, $penjual->hutang - $record->bayar_hutang);
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
