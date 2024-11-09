<?php

namespace App\Filament\Resources\KategoriOperasionalResource\Pages;

use App\Filament\Resources\KategoriOperasionalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategoriOperasionals extends ListRecords
{
    protected static string $resource = KategoriOperasionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
