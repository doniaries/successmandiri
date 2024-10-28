<?php

namespace App\Filament\Resources\PabrikResource\Pages;

use App\Filament\Resources\PabrikResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPabrik extends EditRecord
{
    protected static string $resource = PabrikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
