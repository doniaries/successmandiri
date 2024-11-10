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
use Filament\Resources\Pages\ListRecords\Tab; // Tambahkan import ini
use Illuminate\Database\Eloquent\Builder;

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

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Transaksi')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge(fn() => $this->getModel()::count())
                ->badgeColor('primary'),

            'tunai' => Tab::make('Pembayaran Tunai')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cara_bayar', 'Tunai'))
                ->icon('heroicon-o-banknotes')
                ->badge(fn() => $this->getModel()::where('cara_bayar', 'Tunai')->count())
                ->badgeColor('success'),

            'transfer' => Tab::make('Pembayaran Transfer')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cara_bayar', 'Transfer'))
                ->icon('heroicon-o-credit-card')
                ->badge(fn() => $this->getModel()::where('cara_bayar', 'Transfer')->count())
                ->badgeColor('info'),

            'cair di luar' => Tab::make('Cair Di Luar')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status_bayar', 'Cair Di Luar'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getModel()::where('status_bayar', 'Cair Di Luar')->count())
                ->badgeColor('success'),

        ];
    }
}
