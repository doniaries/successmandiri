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
use Ariaieboy\FilamentCurrency\Forms\Components\CurrencyInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TransaksiDoResource\Pages;

class TransaksiDoResource extends Resource
{
    protected static ?string $model = TransaksiDo::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';



    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor')
                    ->label('Nomor DO')
                    ->default(fn() => 'DO-' . str_pad((static::getModel()::withTrashed()->max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT))
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DateTimePicker::make('tanggal')
                    ->label('Tanggal')
                    ->displayFormat('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('penjual', 'nama')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if (!$state) {
                            $set('hutang', 0);
                            return;
                        }

                        $penjual = Penjual::find($state);
                        if ($penjual) {
                            $set('hutang', $penjual->hutang);
                            $total = (int) $get('total');
                            $upahBongkar = (int) $get('upah_bongkar') ?? 0;
                            $sisaBayar = $total - $upahBongkar - $penjual->hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('nomor_polisi')
                    ->label('Nomor Polisi')
                    ->placeholder('Masukkan Nomor Polisi'),

                Forms\Components\TextInput::make('tonase')
                    ->label('Tonase')
                    ->live(onBlur: true)
                    ->required()
                    ->numeric()
                    ->suffix('Kg')
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state && $get('harga_satuan')) {
                            $total = (int)$state * (int)$get('harga_satuan');
                            $set('total', $total);

                            $upahBongkar = (int)$get('upah_bongkar') ?? 0;
                            $hutang = (int)$get('hutang') ?? 0;
                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->numeric()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state && $get('tonase')) {
                            $total = (int)$get('tonase') * (int)$state;
                            $set('total', $total);

                            $upahBongkar = (int)$get('upah_bongkar') ?? 0;
                            $hutang = (int)$get('hutang') ?? 0;
                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('total')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->label('Total')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('upah_bongkar')
                    ->label('Upah Bongkar')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $total = (int)$get('total') ?? 0;
                        $hutang = (int)$get('hutang') ?? 0;
                        $sisaBayar = $total - (int)$state - $hutang;
                        $set('sisa_bayar', $sisaBayar);
                    }),

                Forms\Components\TextInput::make('hutang')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->label('Hutang')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('bayar_hutang')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->label('Bayar Hutang')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
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
                    }),

                Forms\Components\TextInput::make('sisa_hutang')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->label('Sisa Hutang')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('sisa_bayar')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->prefix('Rp')
                    ->label('Sisa Bayar')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\FileUpload::make('file_do')
                    ->label('File DO')
                    ->directory('do-files')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(5120),

                Forms\Components\Select::make('cara_bayar')
                    ->label('Cara Bayar')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Transfer' => 'Transfer',
                    ])
                    ->default('Tunai')
                    ->required(),

                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor')
                    ->badge()
                    ->copyable()
                    ->color(Color::Blue)
                    ->searchable(),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiDos::route('/'),
            'create' => Pages\CreateTransaksiDo::route('/create'),
            'edit' => Pages\EditTransaksiDo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder  // Return type yang benar
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
