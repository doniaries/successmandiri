<?php

namespace App\Filament\Resources\OperasionalResource\Pages;

use App\Filament\Resources\OperasionalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditOperasional extends EditRecord
{
    protected static string $resource = OperasionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        if (
            $this->record->operasional === 'pengeluaran' &&
            $this->record->kategori?->nama === 'Pinjaman'
        ) {
            Notification::make()
                ->title('Hutang berhasil diperbarui')
                ->success()
                ->send();
        }
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\DeleteAction::make()
    //             ->after(function () {
    //                 if (
    //                     $this->record->operasional === 'pengeluaran' &&
    //                     $this->record->kategori?->nama === 'Pinjaman'
    //                 ) {
    //                     Notification::make()
    //                         ->title('Hutang berhasil dihapus')
    //                         ->success()
    //                         ->send();
    //                 }
    //             }),
    //     ];
    // }
}