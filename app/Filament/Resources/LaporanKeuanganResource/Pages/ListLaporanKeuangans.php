<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Resources\LaporanKeuanganResource;
use App\Filament\Resources\LaporanKeuanganResource\Widgets\LaporanKeuanganDoStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganDoStatsWidget::class,
        ];
    }


    protected function getHeaderActions(): array
    {
        return [];
    }
}
