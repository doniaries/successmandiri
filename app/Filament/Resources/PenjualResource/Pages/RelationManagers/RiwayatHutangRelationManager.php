<?php

namespace App\Filament\Resources\PenjualResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RiwayatHutangRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatHutang';
    protected static ?string $title = 'Riwayat Hutang';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'penambahan' => 'danger',
                        'pengurangan' => 'success',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('operasional.kategori.nama')
                    ->label('Kategori'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->options([
                        'penambahan' => 'Penambahan',
                        'pengurangan' => 'Pengurangan'
                    ]),
            ]);
    }
}
