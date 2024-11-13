<?php

namespace App\Filament\Resources\PenjualResource\Widgets;

use App\Models\Penjual;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PenjualHutangTertinggiWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Penjual::query()
                    ->where('hutang', '>', 0)
                    ->orderBy('hutang', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('telepon')
                    ->label('Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('hutang')
                    ->label('Total Hutang')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn(Penjual $record): string => route('filament.admin.resources.penjuals.view', $record))
                    ->icon('heroicon-m-eye'),
            ])
            ->paginated([5])
            ->heading('Penjual dengan Hutang Tertinggi');
    }
}