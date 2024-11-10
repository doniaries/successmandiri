<?php

namespace App\Filament\Resources\KeuanganResource\Pages;

use App\Filament\Resources\KeuanganResource;
use App\Filament\Resources\KeuanganResource\Widgets\KeuanganStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKeuangans extends ListRecords
{
    protected static string $resource = KeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            KeuanganStatsWidget::class,
        ];
    }
}
