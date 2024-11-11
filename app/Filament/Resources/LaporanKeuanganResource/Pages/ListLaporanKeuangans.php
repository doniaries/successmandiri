<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\LaporanKeuanganResource;
use App\Filament\Resources\LaporanKeuanganResource\Widgets\LaporankeuanganStatWidget;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporankeuanganStatWidget::class,

        ];
    }
}
