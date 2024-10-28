<?php

namespace App\Filament\Resources\PekerjaResource\Pages;

use App\Filament\Resources\PekerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePekerja extends CreateRecord
{

    protected static string $resource = PekerjaResource::class;

    // Redirect ke index setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Dynamic notification for create
    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->record;

        return Notification::make()
            ->success()
            ->title('Data pekerja berhasil ditambahkan')
            ->body("Pekerja {$record->nama} telah ditambahkan ke database.")
            // ->persistent() // Bisa ditambahkan jika ingin notifikasi tetap ada sampai di-close
            ->duration(5000);
    }

    // Mengisi created_by dan updated_by
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}