<?php

namespace App\Filament\Widgets;

use App\Models\TransaksiDo;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Colors\Color;

class TransaksiTerakhir extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|null $defaultTableRecordsPerPageSelectOption = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(TransaksiDo::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor DO')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penjual.nama')
                    ->label('Penjual')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tonase')
                    ->label('Tonase')
                    ->suffix(' Kg')
                    ->numeric(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->color(Color::Emerald)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->label('Sisa Bayar')
                    ->money('IDR')
                    ->color(Color::Red),

                Tables\Columns\TextColumn::make('cara_bayar')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Tunai' => 'success',
                        'Transfer' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data): mixed {
                        return $query
                            ->when(
                                $data['from'],
                                fn($query, $date): mixed => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn($query, $date): mixed => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->defaultSort('created_at', 'desc');
    }
}
