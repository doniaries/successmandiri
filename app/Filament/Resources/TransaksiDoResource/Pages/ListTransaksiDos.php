<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDoStatWidget;
use App\Filament\Widgets\TransaksiDOWidget;
use App\Filament\Widgets\TransaksiWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiDos extends ListRecords
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransaksiDoStatWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            //
        ];
    }
}
