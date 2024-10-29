<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Penjual;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransaksiDo;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiDoResource\Pages;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;
use Filament\Support\Colors\Color;

class TransaksiDoResource extends Resource
{
    protected static ?string $model = TransaksiDo::class;

    // protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    // protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor')
                    ->label('Nomor DO')
                    ->default(function () {
                        $today = now()->format('Ymd');

                        // Ambil ID terakhir + 1 sebagai nomor urut
                        $nextId = (static::getModel()::withTrashed()->max('id') ?? 0) + 1;

                        // Format nomor dengan ID sebagai urutan
                        return "DO-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                    })
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\DateTimePicker::make('tanggal')
                    ->label('Tanggal')
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('penjual_id')
                    ->label('Penjual')
                    ->required()
                    ->relationship('penjual', 'nama')
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if (!$state) {
                            $set('hutang', 0);
                            return;
                        }

                        $penjual = Penjual::find($state);
                        if ($penjual) {
                            // Cast ke float untuk memastikan format angka benar
                            $hutangPenjual = (float) $penjual->hutang;
                            $set('hutang', $hutangPenjual);

                            // Hitung sisa bayar
                            $total = floatval($get('total'));
                            $upahBongkar = floatval($get('upah_bongkar') ?? 0);
                            $sisaBayar = $total - $upahBongkar - $hutangPenjual;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),
                Forms\Components\TextInput::make('nomor_polisi')
                    ->placeholder('Nomor Polisi'),

                Forms\Components\TextInput::make('tonase')
                    ->label('Tonase')
                    ->required()
                    ->suffix('Kg')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Kita pastikan nilai state dan harga_satuan bukan null
                        if ($state && $get('harga_satuan')) {
                            // Convert ke integer untuk kalkulasi yang akurat
                            $tonase = (int)$state;
                            $hargaSatuan = (int)str_replace(['Rp', '.', ','], '', $get('harga_satuan'));

                            // Hitung total
                            $total = $tonase * $hargaSatuan;
                            $set('total', $total);

                            // Hitung sisa bayar
                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');
                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('harga_satuan')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->label('Harga Satuan')
                    ->required()
                    ->prefix('Rp ')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state && $get('tonase')) {
                            // Convert ke integer untuk kalkulasi yang akurat
                            $hargaSatuan = (int)str_replace(['Rp', '.', ','], '', $state);
                            $tonase = (int)$get('tonase');

                            // Hitung total
                            $total = $tonase * $hargaSatuan;
                            $set('total', $total);

                            // Hitung sisa bayar
                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');
                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('total')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->label('Total')
                    ->prefix('Rp ')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('upah_bongkar')
                    ->mask(RawJs::make('$money($input)')) //pemisah titik pada angka
                    ->stripCharacters(',') //koma pada angka
                    ->label('Upah Bongkar')
                    ->numeric()
                    ->prefix('Rp ')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $upahBongkar = floatval($state);
                        $tonase = floatval($get('tonase'));
                        $hargaSatuan = floatval($get('harga_satuan'));

                        $total = $tonase * $hargaSatuan;
                        $hutang = floatval($get('hutang') ?? 0);

                        $sisaBayar = $total - $upahBongkar - $hutang;
                        $set('sisa_bayar', $sisaBayar);
                    }),

                Forms\Components\TextInput::make('hutang')
                    ->mask(RawJs::make('$money($input)')) //pemisah titik pada angka
                    ->stripCharacters(',') //koma pada angka
                    ->label('Hutang')
                    ->prefix('Rp ')
                    ->numeric()
                    ->dehydrated(true) // Tetap simpan ke database
                    ->disabled() // Jadikan read-only karena diisi otomatis
                    ->default(0),
                Forms\Components\TextInput::make('bayar_hutang')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->label('Bayar Hutang')
                    ->prefix('Rp ')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state) {
                            $bayarHutang = (int)str_replace(['Rp', '.', ','], '', $state);
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');
                            $total = (int)str_replace(['Rp', '.', ','], '', $get('total') ?? '0');
                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');

                            // Validasi pembayaran tidak melebihi hutang
                            if ($bayarHutang > $hutang) {
                                $bayarHutang = $hutang;
                                $set('bayar_hutang', $hutang);
                            }

                            // Hitung sisa hutang
                            $sisaHutang = $hutang - $bayarHutang;
                            $set('sisa_hutang', $sisaHutang);

                            // Hitung sisa bayar
                            $sisaBayar = $total - $upahBongkar - $bayarHutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('sisa_hutang')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->label('Sisa Hutang')
                    ->prefix('Rp ')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('sisa_bayar')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->label('Sisa Bayar')
                    ->prefix('Rp ')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\FileUpload::make('file_do')
                    ->label('File DO'),
                Forms\Components\Select::make('cara_bayar')
                    ->label('Cara Bayar')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Transfer' => 'Transfer',
                    ])
                    ->default('Tunai')
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->badge()
                    ->color(function ($state) {
                        return Color::Blue;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('penjual.nama')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_polisi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tonase')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_satuan')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->color(Color::Emerald)
                    ->weight('bold')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hutang')
                    ->color(Color::Red)
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bayar_hutang')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_hutang')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cara_bayar')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Tunai' => 'success',
                        'Transfer' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('file_do')
                    ->label('File DO')
                    ->url(fn($record) => $record->file_do)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
}
