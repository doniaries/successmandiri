<?php

namespace App\Filament\Resources\PenjualResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RiwayatHutangRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatHutang';
    protected static ?string $title = 'Riwayat Hutang';
    protected static ?string $modelLabel = 'Riwayat Hutang';
    protected static ?string $pluralModelLabel = 'Riwayat Hutang';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'penambahan' => 'danger',
                        'pengurangan' => 'success',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hutang_sebelum')
                    ->label('Hutang Sebelum')
                    ->money('IDR')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('hutang_sesudah')
                    ->label('Hutang Sesudah')
                    ->money('IDR')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}
