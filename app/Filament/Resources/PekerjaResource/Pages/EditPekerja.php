<?php

namespace App\Filament\Resources\PekerjaResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PekerjaResource;
use App\Filament\Traits\HasDynamicNotification;

class EditPekerja extends EditRecord
{

    use HasDynamicNotification;

    protected static string $resource = PekerjaResource::class;

    // Redirect ke index setelah edit
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    // Mengisi updated_by
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}