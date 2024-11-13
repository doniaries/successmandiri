<?php

namespace App\Filament\Resources\PenjualResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RiwayatHutangRelationManager extends RelationManager // Ubah nama kelas
{
    protected static string $relationship = 'riwayatHutang'; // Sesuaikan dengan nama relasi di model
    protected static ?string $title = 'Riwayat Hutang'; // Ubah judul
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
                    ->color(fn(string $state): string => match ($state) {
                        'penambahan' => 'danger',
                        'pengurangan' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),

                Tables\Columns\TextColumn::make('operasional.nomor')
                    ->label('No. Referensi')
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