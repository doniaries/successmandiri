<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeuanganResource\Pages;
use App\Models\Keuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\KeuanganResource\Widgets;
use Illuminate\Database\Eloquent\Builder;

class KeuanganResource extends Resource
{
    protected static ?string $model = Keuangan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('tanggal')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('jenis')
                            ->options([
                                'pemasukan' => 'Pemasukan',
                                'pengeluaran' => 'Pengeluaran',
                            ])
                            ->required(),

                        Forms\Components\Select::make('kategori')
                            ->options([
                                'transaksi_do' => 'Transaksi DO',
                                'bayar_hutang' => 'Pembayaran Hutang',
                                'lainnya' => 'Lainnya',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->currencyMask(
                                thousandSeparator: '.',
                                decimalSeparator: ',',
                                precision: 0,
                            ),

                        Forms\Components\Textarea::make('keterangan')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('kategori')
                    ->badge(),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->color(
                        fn($record): string =>
                        $record->jenis === 'pemasukan' ? 'success' : 'danger'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'transaksi_do' => 'Transaksi DO',
                        'bayar_hutang' => 'Pembayaran Hutang',
                        'lainnya' => 'Lainnya',
                    ]),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date)
                            );
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKeuangans::route('/'),
            'create' => Pages\CreateKeuangan::route('/create'),
            'edit' => Pages\EditKeuangan::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\KeuanganStatsWidget::class,
            Widgets\KeuanganChartWidget::class,
        ];
    }
}
