<?php

namespace App\Filament\Resources\PenjualResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PenjualResource;
use App\Filament\Traits\HasDynamicNotification;

class CreatePenjual extends CreateRecord
{
    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = PenjualResource::class;
}