<?php

namespace App\Filament\Resources\PabrikResource\Pages;

use App\Filament\Resources\PabrikResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePabrik extends CreateRecord
{
    protected static string $resource = PabrikResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Kustomisasi notifikasi sukses
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Pabrik baru ditambahkan')
            ->body("Data pabrik {$this->record->nama} berhasil ditambahkan ke database.");
    }
}