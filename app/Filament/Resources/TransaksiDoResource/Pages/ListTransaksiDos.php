<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDoStatWidget;
use App\Filament\Widgets\TransaksiDOWidget;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiDos extends ListRecords
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function getActions(): array
    {
        return [
            //
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
