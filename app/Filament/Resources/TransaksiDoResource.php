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
                    ->disabled()
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
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if (!$state) {
                            $set('hutang', 0);
                            return;
                        }

                        $penjual = Penjual::find($state);
                        if ($penjual) {
                            // Format hutang dengan pemisah ribuan
                            $hutangFormatted = number_format($penjual->hutang, 0, '', '.');
                            $set('hutang', $hutangFormatted);

                            // Hitung sisa bayar
                            $total = floatval($get('total'));
                            $upahBongkar = floatval($get('upah_bongkar') ?? 0);
                            $sisaBayar = $total - $upahBongkar - $penjual->hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),
                Forms\Components\TextInput::make('nomor_polisi')
                    ->placeholder('Nomor Polisi'),

                // Input Tonase: Menerima input numeric dan menghitung total
                Forms\Components\TextInput::make('tonase')
                    ->label('Tonase')
                    ->required()
                    ->suffix('Kg')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Hanya proses jika tonase dan harga satuan ada
                        if ($state && $get('harga_satuan')) {
                            // Bersihkan format uang dari harga satuan dan konversi ke integer
                            $tonase = (int)$state;
                            $hargaSatuan = (int)str_replace(['Rp', '.', ','], '', $get('harga_satuan'));

                            // Hitung total dan format dengan number_format untuk tampilan dengan titik
                            $total = $tonase * $hargaSatuan;
                            $formattedTotal = number_format($total, 0, '', '.');
                            $set('total', $formattedTotal);

                            // Bersihkan format uang dari input lain untuk perhitungan sisa bayar
                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');

                            // Hitung dan format sisa bayar
                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $formattedSisaBayar = number_format($sisaBayar, 0, '', '.');
                            $set('sisa_bayar', $formattedSisaBayar);
                        }
                    }),
                Forms\Components\TextInput::make('harga_satuan')
                    ->prefix('Rp')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->label('Harga Satuan')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state && $get('tonase')) {
                            $hargaSatuan = (int)str_replace(['Rp', '.', ','], '', $state);
                            $tonase = (int)$get('tonase');

                            $total = $tonase * $hargaSatuan;
                            $formattedTotal = number_format($total, 0, '', '.');
                            $set('total', $formattedTotal);

                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');

                            $sisaBayar = $total - $upahBongkar - $hutang;
                            $formattedSisaBayar = number_format($sisaBayar, 0, '', '.');
                            $set('sisa_bayar', $formattedSisaBayar);
                        }
                    }),

                // Field Total: Tampilkan hasil perhitungan dengan format
                Forms\Components\TextInput::make('total')
                    ->prefix('Rp')
                    ->label('Total')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('upah_bongkar')
                    ->prefix('Rp')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->label('Upah Bongkar')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        // Bersihkan format untuk kalkulasi
                        $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $state);
                        $tonase = (int)str_replace(['Rp', '.', ','], '', $get('tonase'));
                        $hargaSatuan = (int)str_replace(['Rp', '.', ','], '', $get('harga_satuan'));
                        $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');

                        $total = $tonase * $hargaSatuan;

                        // Format sisa bayar dengan pemisah ribuan
                        $sisaBayar = $total - $upahBongkar - $hutang;
                        $formattedSisaBayar = number_format($sisaBayar, 0, '', '.');
                        $set('sisa_bayar', $formattedSisaBayar);
                    }),

                Forms\Components\TextInput::make('hutang')
                    ->prefix('Rp')
                    // ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->label('Hutang')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('bayar_hutang')
                    ->prefix('Rp')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->label('Bayar Hutang')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state) {
                            // Bersihkan format angka untuk kalkulasi
                            $bayarHutang = (int)str_replace(['Rp', '.', ','], '', $state);
                            $hutang = (int)str_replace(['Rp', '.', ','], '', $get('hutang') ?? '0');
                            $total = (int)str_replace(['Rp', '.', ','], '', $get('total') ?? '0');
                            $upahBongkar = (int)str_replace(['Rp', '.', ','], '', $get('upah_bongkar') ?? '0');

                            // Validasi pembayaran tidak melebihi hutang
                            if ($bayarHutang > $hutang) {
                                $bayarHutang = $hutang;
                                $formattedBayarHutang = number_format($hutang, 0, '', '.');
                                $set('bayar_hutang', $formattedBayarHutang);
                            }

                            // Hitung dan format sisa hutang
                            $sisaHutang = $hutang - $bayarHutang;
                            $formattedSisaHutang = number_format($sisaHutang, 0, '', '.');
                            $set('sisa_hutang', $formattedSisaHutang);

                            // Hitung dan format sisa bayar
                            $sisaBayar = $total - $upahBongkar - $bayarHutang;
                            $formattedSisaBayar = number_format($sisaBayar, 0, '', '.');
                            $set('sisa_bayar', $formattedSisaBayar);
                        }
                    }),

                Forms\Components\TextInput::make('sisa_hutang')
                    ->prefix('Rp')
                    ->label('Sisa Hutang')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('sisa_bayar')
                    ->prefix('Rp')
                    ->label('Sisa Bayar')
                    ->disabled()
                    ->dehydrated(true),

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
                    ->copyable()
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
                MoneyColumn::make('harga_satuan')
                    ->currency('IDR')
                    ->locale('id-ID')
                    ->decimals(0)
                    ->sortable(),
                MoneyColumn::make('total')
                    ->currency('IDR')
                    ->decimals(0)
                    ->locale('id-ID')
                    ->color(Color::Emerald)
                    ->weight('bold')
                    ->alignEnd()
                    ->sortable(),

                MoneyColumn::make('upah_bongkar')
                    ->currency('IDR')
                    ->decimals(0)
                    ->locale('id-ID')
                    ->alignEnd()
                    ->sortable(),

                MoneyColumn::make('hutang')
                    ->color(Color::Red)
                    ->currency('IDR')
                    ->decimals(0)
                    ->locale('id-ID')
                    ->sortable(),

                MoneyColumn::make('bayar_hutang')
                    ->currency('IDR')
                    ->decimals(0)
                    ->locale('id-ID')
                    ->alignEnd()
                    ->sortable(),

                MoneyColumn::make('sisa_hutang')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),

                MoneyColumn::make('sisa_bayar')
                    ->currency('IDR')
                    ->decimals(0)
                    ->locale('id-ID')
                    ->weight('bold')
                    ->color(Color::Emerald)
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
