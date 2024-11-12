<?php

namespace App\Filament\Resources\RiwayatHutangResource\Pages;

use App\Filament\Resources\RiwayatHutangResource;
use Filament\Resources\Pages\ListRecords;

class ListRiwayatHutang extends ListRecords
{
    protected static string $resource = RiwayatHutangResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
