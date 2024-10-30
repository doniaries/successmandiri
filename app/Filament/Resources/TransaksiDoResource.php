<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Penjual;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransaksiDo;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiDoResource\Pages;
use Filament\Tables\Columns\ColorColumn;

class TransaksiDoResource extends Resource
{
    protected static ?string $model = TransaksiDo::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Transaksi DO';
    protected static ?string $modelLabel = 'Transaksi DO';
    protected static ?string $pluralModelLabel = 'Transaksi DO';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Header Section - Informasi Utama
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('nomor')
                                ->label('Nomor DO')
                                ->default(fn() => 'DO-' . str_pad((static::getModel()::withTrashed()->max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT))
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\DateTimePicker::make('tanggal')
                                ->label('Tanggal')
                                ->timezone('Asia/Jakarta')
                                ->displayFormat('d/m/Y H:i')
                                ->default(now())
                                ->required()
                                ->dehydrated(),

                            Forms\Components\Select::make('penjual_id')
                                ->label('Penjual')
                                ->relationship('penjual', 'nama')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('nama')
                                        ->label('Nama'),
                                    Forms\Components\TextInput::make('alamat')
                                        ->label('Alamat'),
                                    Forms\Components\TextInput::make('telepon')
                                        ->label('Telepon/HP'),
                                ])

                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $hutang = Penjual::find($state)?->hutang ?? 0;
                                        $set('hutang', $hutang);
                                    } else {
                                        $set('hutang', 0);
                                    }
                                })
                                ->required(),

                            Forms\Components\TextInput::make('nomor_polisi')
                                ->label('Nomor Polisi'),
                        ])
                        ->columns(2),
                ])
                ->columnSpanFull(),

            // Detail Pengiriman Section
            Forms\Components\Grid::make()
                ->schema([
                    // Panel Kiri
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('tonase')
                                        ->label('Tonase (Netto)')
                                        ->required()
                                        ->numeric()
                                        ->suffix('Kg')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungTotal($state, $get, $set)),

                                    Forms\Components\TextInput::make('harga_satuan')
                                        ->label('Harga Satuan')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->required()
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungTotal($state, $get, $set)),
                                ])
                                ->columns(2),
                        ])
                        ->columnSpan(2),

                    // Panel Kanan
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('total')
                                ->label('Sub Total')
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            // Perhitungan & Pembayaran Section
            Forms\Components\Grid::make()
                ->schema([
                    // Panel Kiri - Detail Pembayaran
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('upah_bongkar')
                                        ->label('Upah Bongkar')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->prefix('Rp')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungSisaBayar($state, $get, $set)),

                                    Forms\Components\TextInput::make('bayar_hutang')
                                        ->label('Bayar Hutang')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->prefix('Rp')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungPembayaranHutang($state, $get, $set)),
                                    Forms\Components\Select::make('cara_bayar')
                                        ->label('Metode Pembayaran')
                                        ->options([
                                            'Tunai' => 'Tunai',
                                            'Transfer' => 'Transfer',
                                        ])
                                        ->default('Tunai')
                                        ->required(),
                                    Forms\Components\TextInput::make('catatan')
                                        ->label('Catatan'),
                                    Forms\Components\FileUpload::make('file_do')
                                        ->label('Upload File DO')
                                        ->directory('do-files')
                                        ->preserveFilenames()
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->maxSize(5120),


                                ])
                                ->columns(2),
                        ])
                        ->columnSpan(2),

                    // Panel Kanan - Informasi Hutang & Sisa
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('hutang')
                                        ->label('Total Hutang')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->dehydrated()
                                        ->numeric()
                                        ->default(0),

                                    Forms\Components\TextInput::make('sisa_hutang')
                                        ->label('Sisa Hutang')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\TextInput::make('sisa_bayar')
                                        ->label('Sisa Bayar')
                                        ->prefix('Rp')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->disabled()
                                        ->dehydrated(),


                                ])
                                ->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),


        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penjual.nama')
                    ->label('Penjual')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_polisi')
                    ->label('Nomor Polisi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tonase')
                    ->label('Tonase')
                    ->suffix(' Kg')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->color(Color::Emerald)
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->label('Upah Bongkar')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hutang')
                    ->label('Hutang')
                    ->money('IDR')
                    ->color(Color::Red)
                    ->sortable(),

                Tables\Columns\TextColumn::make('bayar_hutang')
                    ->label('Bayar Hutang')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_hutang')
                    ->label('Sisa Hutang')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->label('Sisa Bayar')
                    ->money('IDR')
                    ->color(Color::Emerald)
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cara_bayar')
                    ->label('Cara Bayar')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Tunai' => 'success',
                        'Transfer' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    // Helper methods untuk kalkulasi
    private static function hitungTotal($state, Forms\Get $get, Forms\Set $set): void
    {
        $tonase = (int)$get('tonase');
        $hargaSatuan = (int)$get('harga_satuan');

        if ($tonase && $hargaSatuan) {
            $total = $tonase * $hargaSatuan;
            $set('total', $total);

            $upahBongkar = (int)$get('upah_bongkar') ?? 0;
            $hutang = (int)$get('hutang') ?? 0;
            $sisaBayar = $total - $upahBongkar - $hutang;
            $set('sisa_bayar', $sisaBayar);
        }
    }

    private static function hitungSisaBayar($state, Forms\Get $get, Forms\Set $set): void
    {
        $total = (int)$get('total') ?? 0;
        $hutang = (int)$get('hutang') ?? 0;
        $sisaBayar = $total - (int)$state - $hutang;
        $set('sisa_bayar', $sisaBayar);
    }

    private static function hitungPembayaranHutang($state, Forms\Get $get, Forms\Set $set): void
    {
        $hutang = (int)$get('hutang') ?? 0;
        $total = (int)$get('total') ?? 0;
        $upahBongkar = (int)$get('upah_bongkar') ?? 0;

        // Validasi pembayaran tidak melebihi hutang
        if ((int)$state > $hutang) {
            $set('bayar_hutang', $hutang);
            $state = $hutang;
        }

        $sisaHutang = $hutang - (int)$state;
        $set('sisa_hutang', $sisaHutang);

        $sisaBayar = $total - $upahBongkar - (int)$state;
        $set('sisa_bayar', $sisaBayar);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiDos::route('/'),
            'create' => Pages\CreateTransaksiDo::route('/create'),
            'edit' => Pages\EditTransaksiDo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hitung sisa hutang
        $hutang = (int)str_replace(['.', ','], ['', '.'], $data['hutang'] ?? 0);
        $bayarHutang = (int)str_replace(['.', ','], ['', '.'], $data['bayar_hutang'] ?? 0);
        $data['sisa_hutang'] = $hutang - $bayarHutang;

        // Format angka lainnya
        $numericFields = [
            'tonase',
            'harga_satuan',
            'total',
            'upah_bongkar',
            'hutang',
            'bayar_hutang',
            'sisa_bayar'
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int)str_replace(['.', ','], ['', '.'], $data[$field]);
            }
        }

        return $data;
    }
}
