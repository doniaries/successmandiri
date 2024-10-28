<?php

namespace App\Filament\Resources\PabrikResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use App\Filament\Resources\PabrikResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Traits\HasDynamicNotification;

class CreatePabrik extends CreateRecord
{

    use HasDynamicNotification;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected static string $resource = PabrikResource::class;
}