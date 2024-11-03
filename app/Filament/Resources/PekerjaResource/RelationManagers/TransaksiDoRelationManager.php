<?php

namespace App\Filament\Resources\PekerjaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TransaksiDoRelationManager extends RelationManager
{
    protected static string $relationship = 'transaksiDos';
    protected static ?string $title = 'Riwayat Pendapatan';
    protected static ?string $recordTitleAttribute = 'nomor';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor')
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor DO')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penjual.nama')
                    ->label('Penjual')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pivot.pendapatan_pekerja')
                    ->label('Pendapatan')
                    ->money('IDR')
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Pendapatan')
                            ->money('IDR')
                    ),

                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->label('Total Upah Bongkar')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('pekerja_count')
                    ->label('Jumlah Pekerja')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return $record->pekerjas->count();
                    })
                    ->color('success'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date)
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->infolist(fn(Infolist $infolist): Infolist => $this->infolist($infolist)),
            ])
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Pekerja ini belum memiliki riwayat transaksi DO')
            ->poll('30s');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor')
                            ->label('Nomor DO'),

                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('pivot.pendapatan_pekerja')
                            ->label('Pendapatan')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('pekerja_count')
                            ->label('Jumlah Pekerja')
                            ->getStateUsing(fn($record) => $record->pekerjas->count()),

                        Infolists\Components\TextEntry::make('upah_bongkar')
                            ->label('Total Upah Bongkar')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('catatan')
                            ->label('Keterangan')
                            ->default('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Daftar Pekerja')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('pekerjas')
                            ->schema([
                                Infolists\Components\TextEntry::make('nama')
                                    ->label('Nama'),
                                Infolists\Components\TextEntry::make('pivot.pendapatan_pekerja')
                                    ->label('Pendapatan')
                                    ->money('IDR'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(fn($record) => $record->pekerjas->isNotEmpty()),
            ]);
    }
}
