<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiDoResource\Pages;
use App\Models\TransaksiDo;
use App\Models\Penjual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;

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
                    // ->autofocus()
                    ->relationship('penjual', 'nama')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if (!$state) {
                            $set('hutang', 0);
                            return;
                        }

                        // Ambil data penjual dan set hutang
                        $penjual = Penjual::find($state);
                        if ($penjual) {
                            $set('hutang', $penjual->hutang); // Menggunakan kolom hutang, bukan total_hutang

                            // Hitung ulang sisa bayar
                            $total = floatval($get('total'));
                            $upahBongkar = floatval($get('upah_bongkar') ?? 0);
                            $sisaBayar = $total - $upahBongkar - $penjual->hutang;
                            $set('sisa_bayar', $sisaBayar);
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('nomor_polisi')
                    ->placeholder('Nomor Polisi'),

                Forms\Components\TextInput::make('tonase')
                    ->label('Tonase')
                    ->required()
                    ->suffix('Kg')
                    ->numeric()
                    // ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $tonase = floatval($state);
                        $hargaSatuan = floatval($get('harga_satuan'));

                        $total = $tonase * $hargaSatuan;
                        $set('total', $total);

                        $upahBongkar = floatval($get('upah_bongkar') ?? 0);
                        $hutang = floatval($get('hutang') ?? 0);

                        $sisaBayar = $total - $upahBongkar - $hutang;
                        $set('sisa_bayar', $sisaBayar);
                    }),

                MoneyInput::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->required()
                    ->currency('IDR')
                    ->locale('id_ID')
                    // ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $hargaSatuan = floatval($state);
                        $tonase = floatval($get('tonase'));

                        $total = $tonase * $hargaSatuan;
                        $set('total', $total);

                        $upahBongkar = floatval($get('upah_bongkar') ?? 0);
                        $hutang = floatval($get('hutang') ?? 0);

                        $sisaBayar = $total - $upahBongkar - $hutang;
                        $set('sisa_bayar', $sisaBayar);
                    }),

                MoneyInput::make('total')
                    ->label('Total')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->disabled()
                    ->dehydrated(),

                MoneyInput::make('upah_bongkar')
                    ->label('Upah Bongkar')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0)
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

                MoneyInput::make('hutang')
                    ->label('Hutang')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->disabled() // Jadikan read-only karena diisi otomatis
                    ->dehydrated() // Tetap simpan ke database
                    ->default(0),

                MoneyInput::make('bayar_hutang')
                    ->label('Bayar Hutang')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $bayarHutang = floatval($state);
                        $hutang = floatval($get('hutang'));
                        $total = floatval($get('total'));
                        $upahBongkar = floatval($get('upah_bongkar') ?? 0);

                        // Validasi pembayaran tidak melebihi hutang
                        if ($bayarHutang > $hutang) {
                            $bayarHutang = $hutang;
                            $set('bayar_hutang', $hutang);
                        }

                        // Sisa bayar = Total - Upah Bongkar - Bayar Hutang
                        $sisaBayar = $total - $upahBongkar - $bayarHutang;
                        $set('sisa_bayar', $sisaBayar);
                    }),

                MoneyInput::make('sisa_bayar')
                    ->label('Sisa Bayar')
                    ->currency('IDR')
                    ->locale('id_ID')
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
                    ->sortable(),

                MoneyColumn::make('total')
                    ->currency('IDR')
                    ->sortable(),

                MoneyColumn::make('upah_bongkar')
                    ->currency('IDR')
                    ->sortable(),

                MoneyColumn::make('hutang')
                    ->currency('IDR')
                    ->sortable(),

                MoneyColumn::make('bayar_hutang')
                    ->currency('IDR')
                    ->sortable(),

                MoneyColumn::make('sisa_bayar')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_do')
                    ->label('File DO')
                    ->url(fn($record) => $record->file_do)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cara_bayar'),
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
