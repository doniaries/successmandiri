<?php

namespace App\Filament\Resources\TransaksiDoResource\Pages;

use App\Filament\Resources\TransaksiDoResource;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDoStatWidget;
use App\Filament\Widgets\TransaksiDOWidget;
use App\Filament\Widgets\TransaksiWidget;
use App\Models\Operasional; // Tambahkan ini
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Filament\Actions;  // Ubah import ini
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTransaksiDos extends ListRecords
{
    protected static string $resource = TransaksiDoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),  // Ubah ini

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