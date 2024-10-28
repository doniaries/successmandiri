<?php

namespace App\Filament\Resources\PerusahaanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PerusahaanResource;
use App\Filament\Traits\HasDynamicNotification;

class CreatePerusahaan extends CreateRecord
{

    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = PerusahaanResource::class;
}