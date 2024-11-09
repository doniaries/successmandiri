<?php

namespace App\Filament\Resources\OperasionalResource\Pages;

use App\Filament\Resources\OperasionalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateOperasional extends CreateRecord
{
    protected static string $resource = OperasionalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function afterCreate(): void
    {
        $record = $this->record;

        if (
            $record->operasional === 'pengeluaran' &&
            $record->kategori?->nama === 'Pinjaman'
        ) {
            Notification::make()
                ->title('Hutang berhasil ditambahkan')
                ->success()
                ->send();
        }
    }
}
