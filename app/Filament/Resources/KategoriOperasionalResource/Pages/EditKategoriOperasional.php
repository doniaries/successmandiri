<?php

namespace App\Filament\Resources\KategoriOperasionalResource\Pages;

use App\Filament\Resources\KategoriOperasionalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKategoriOperasional extends EditRecord
{
    protected static string $resource = KategoriOperasionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
