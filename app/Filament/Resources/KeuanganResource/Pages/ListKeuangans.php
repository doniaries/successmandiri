<?php

namespace App\Filament\Resources\KeuanganResource\Pages;

use App\Filament\Resources\KeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

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
            KeuanganResource\Widgets\KeuanganStatsWidget::class,
            KeuanganResource\Widgets\KeuanganChartWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Transaksi')
                ->badge(fn() => static::$resource::getModel()::count()),

            'pemasukan' => Tab::make('Pemasukan')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'pemasukan'))
                ->badge(fn() => static::$resource::getModel()::where('jenis', 'pemasukan')->count()),

            'pengeluaran' => Tab::make('Pengeluaran')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'pengeluaran'))
                ->badge(fn() => static::$resource::getModel()::where('jenis', 'pengeluaran')->count()),
        ];
    }
}
