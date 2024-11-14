<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Resources\LaporanKeuanganResource;
use App\Filament\Resources\LaporanKeuanganResource\Widgets\LaporanKeuanganDoStatsWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ListLaporanKeuangans extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;


    public function getTabs(): array
    {
        return [
            // 'semua' => Tab::make('Semua Transaksi')
            //     ->icon('heroicon-m-clipboard-document-list')
            //     ->badge(self::getModel()::count()),

            // 'pemasukan' => Tab::make('Pemasukan')
            //     ->icon('heroicon-m-arrow-trending-up')
            //     ->badge(self::getModel()::where('jenis', 'masuk')->count())
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'masuk')),

            // 'pengeluaran' => Tab::make('Pengeluaran')
            //     ->icon('heroicon-m-arrow-trending-down')
            //     ->badge(self::getModel()::where('jenis', 'keluar')->count())
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('jenis', 'keluar')),

            // 'transaksi_do' => Tab::make('Transaksi DO')
            //     ->icon('heroicon-m-document-text')
            //     ->badge(self::getModel()::where('tipe_transaksi', 'transaksi_do')->count())
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('tipe_transaksi', 'transaksi_do')),

            // 'operasional' => Tab::make('Operasional')
            //     ->icon('heroicon-m-banknotes')
            //     ->badge(self::getModel()::where('tipe_transaksi', 'operasional')->count())
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('tipe_transaksi', 'operasional')),

            'hari_ini' => Tab::make('Hari Ini')
                ->icon('heroicon-m-calendar-days')
                ->badge(self::getModel()::whereDate('tanggal', today())->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('tanggal', today())),

            'minggu_ini' => Tab::make('Minggu Ini')
                ->icon('heroicon-m-calendar')
                ->badge(self::getModel()::whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()])->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])),

        ];
    }


    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganDoStatsWidget::class,
        ];
    }


    protected function getHeaderActions(): array
    {
        return []; // Harus selalu return array
    }
}
