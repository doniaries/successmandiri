<?php

namespace App\Filament\Resources\PenjualResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransaksiDoRelationManager extends RelationManager
{
    protected static string $relationship = 'transaksiDo';
    protected static ?string $title = 'Transaksi DO';
    protected static ?string $modelLabel = 'Transaksi DO';
    protected static ?string $pluralModelLabel = 'Transaksi DO';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor DO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembayaran_hutang')
                    ->label('Bayar Hutang')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_bayar')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('tanggal', 'desc');
    }
}
