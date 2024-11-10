<?php

namespace App\Filament\Resources\PekerjaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PekerjaResource;
use App\Filament\Traits\HasDynamicNotification;

class CreatePekerja extends CreateRecord
{
    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = PekerjaResource::class;
}