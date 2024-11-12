<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;


use App\Filament\Resources\LaporanKeuanganResource;
use App\Filament\Resources\LaporanKeuanganResource\Widgets\LaporanKeuanganStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganStatsWidget::class,
        ];
    }
    protected function getHeaderActions(): array
    {
        return [];
    }
}
