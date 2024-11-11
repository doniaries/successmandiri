<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Resources\LaporanKeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
