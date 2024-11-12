<?php

namespace App\Filament\Resources\RiwayatHutangResource\Pages;

use App\Filament\Resources\RiwayatHutangResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RiwayatHutangResource\Widgets\RiwayatHutangStatsWidget;

class ListRiwayatHutangs extends ListRecords
{
    protected static string $resource = RiwayatHutangResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RiwayatHutangStatsWidget::class,
        ];
    }
}
