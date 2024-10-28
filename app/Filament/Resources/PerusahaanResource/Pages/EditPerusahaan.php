<?php

namespace App\Filament\Resources\PerusahaanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PerusahaanResource;
use App\Filament\Traits\HasDynamicNotification;

class EditPerusahaan extends EditRecord
{
    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = PerusahaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}