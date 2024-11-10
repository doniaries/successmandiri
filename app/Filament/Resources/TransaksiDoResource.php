<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransaksiDo;
use App\Models\Penjual;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiDoResource\Pages;
use Illuminate\Database\Eloquent\Builder;


class TransaksiDoResource extends Resource
{
    protected static ?string $model = TransaksiDo::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Transaksi DO';
    protected static ?string $modelLabel = 'Transaksi DO';
    protected static ?string $pluralModelLabel = 'Transaksi DO';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getModel(): string
    {
        return TransaksiDo::class;
    }


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
                                // ->default(fn() => 'DO-' . str_pad((static::getModel()::withTrashed()->max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT))
                                ->default(fn() => TransaksiDo::generateMonthlyNumber())
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\DateTimePicker::make('tanggal')
                                ->label('Tanggal')
                                ->timezone('Asia/Jakarta')
                                ->displayFormat('d/m/Y H:i')
                                ->default(now())
                                ->disabled()
                                ->required()
                                ->dehydrated(),

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
                                    Forms\Components\Select::make('penjual_id')
                                        ->label('Penjual')
                                        ->placeholder('Pilih Nama Penjual')
                                        ->relationship('penjual', 'nama')
                                        ->searchable()
                                        ->preload()
                                        ->hint('Tambahkan Penjual Baru')
                                        ->hintIcon('heroicon-m-arrow-down-circle')
                                        ->hintColor('primary')
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nama')
                                                ->required()
                                                ->label('Nama'),
                                            Forms\Components\TextInput::make('alamat')
                                                ->required()
                                                ->label('Alamat'),
                                            Forms\Components\TextInput::make('telepon')
                                                ->required()
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
                                        ->placeholder('BA 1234 K')
                                        ->label('Nomor Polisi'),
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
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                        ->required()
                                        ->default(0)
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
                            Forms\Components\TextInput::make('hutang')
                                ->label('Total Hutang')
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated()
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('total')
                                ->label('Sub Total')
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                ->prefix('Rp')
                                ->extraAttributes(['class' => 'text-xl font-bold text-primary-600'])
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
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungSisaBayar($state, $get, $set)),

                                    Forms\Components\TextInput::make('biaya_lain')
                                        ->label('Biaya Lain')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungSisaBayar($state, $get, $set)),

                                    Forms\Components\TextInput::make('bayar_hutang')
                                        ->label('Bayar Hutang')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                        ->prefix('Rp')
                                        ->default(0)
                                        // ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungPembayaranHutang($state, $get, $set)),
                                    Forms\Components\Select::make('status_bayar')
                                        ->label('Status Bayar')
                                        ->options([
                                            'Lunas' => 'Lunas',
                                            'Belum Lunas' => 'Belum Lunas',
                                        ])
                                        // ->default('Lunas')
                                        ->required(),
                                    Forms\Components\Select::make('cara_bayar')
                                        ->label('Cara Bayar')
                                        ->options([
                                            'Tunai' => 'Tunai',
                                            'Transfer' => 'Transfer',
                                            'Cair di Luar' => 'Cair di Luar',
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
                                ->columns(3),
                        ])
                        ->columnSpan(2),

                    // Panel Kanan - Informasi Hutang & Sisa
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('sisa_hutang')
                                        ->label('Sisa Hutang')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\TextInput::make('sisa_bayar')
                                        ->label('Sisa Bayar')
                                        ->prefix('Rp')
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
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
                Tables\Columns\TextColumn::make('file_do') //image file do
                    ->label('File DO')
                    ->tooltip('klik untuk melihat')
                    ->alignCenter()
                    ->icon('heroicon-m-document')
                    ->color(Color::Emerald)
                    ->formatStateUsing(fn($state) => $state ? 'Lihat' : '-')
                    ->action(
                        Action::make('previewFile')
                            ->modalHeading('Preview File DO')
                            ->modalWidth('4xl')
                            ->modalContent(fn($record) => view(
                                'filament.components.file-viewer',
                                ['url' => Storage::url($record->file_do ?? '')]
                            ))
                    ),

                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('no DO telah disalin')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->badge()
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
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->suffix(' Kg')
                    ])
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->color(Color::Amber)
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->label('Upah Bongkar')
                    ->money('IDR')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('biaya_lain')
                    ->label('Biaya Lain')
                    ->money('IDR'),


                Tables\Columns\TextColumn::make('hutang')
                    ->label('Hutang')
                    ->money('IDR')
                    ->color(Color::Red)
                    ->sortable(),

                Tables\Columns\TextColumn::make('bayar_hutang')
                    ->label('Bayar Hutang')
                    ->money('IDR')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->color(Color::Orange)
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
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_bayar')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'warning',
                        default => 'gray',
                    }),
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
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),

                // ]),
            ]);
    }



    // Helper methods untuk kalkulasi
    private static function hitungTotal($state, Forms\Get $get, Forms\Set $set): void
    {
        $tonase = self::formatCurrency($get('tonase'));
        $hargaSatuan = self::formatCurrency($get('harga_satuan'));

        if ($tonase && $hargaSatuan) {
            $total = $tonase * $hargaSatuan;
            $set('total', $total);

            $upahBongkar = self::formatCurrency($get('upah_bongkar'));
            $biayaLain = self::formatCurrency($get('biaya_lain'));
            $bayarHutang = self::formatCurrency($get('bayar_hutang'));

            $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
            $set('sisa_bayar', max(0, $sisaBayar));
        }
    }

    private static function formatCurrency($number): int
    {
        return (int)str_replace(['.', ','], ['', '.'], $number ?? 0);
    }

    private static function hitungSisaBayar($state, Forms\Get $get, Forms\Set $set): void
    {
        $total = (int)$get('total') ?? 0;
        $upahBongkar = (int)$get('upah_bongkar') ?? 0;
        $biayaLain = (int)$get('biaya_lain') ?? 0;
        $bayarHutang = (int)$get('bayar_hutang') ?? 0;

        // Hitung sisa bayar dengan semua komponen
        $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
        $set('sisa_bayar', $sisaBayar);
    }

    private static function hitungPembayaranHutang($state, Forms\Get $get, Forms\Set $set): void
    {
        $hutang = (int)$get('hutang') ?? 0;
        $bayarHutang = (int)$state ?? 0;

        // Validasi pembayaran tidak melebihi hutang
        if ($bayarHutang > $hutang) {
            $set('bayar_hutang', $hutang);
            $bayarHutang = $hutang;
        }

        // Hitung sisa hutang
        $sisaHutang = $hutang - $bayarHutang;
        $set('sisa_hutang', $sisaHutang);

        // Hitung ulang sisa bayar dengan semua komponen
        $total = (int)$get('total') ?? 0;
        $upahBongkar = (int)$get('upah_bongkar') ?? 0;
        $biayaLain = (int)$get('biaya_lain') ?? 0;

        $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
        $set('sisa_bayar', $sisaBayar);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        // Set default values untuk mencegah NULL
        $defaults = [
            'upah_bongkar' => 0,
            'biaya_lain' => 0,
            'bayar_hutang' => 0,
            'hutang' => 0,
            'total' => 0,
        ];

        $data = array_merge($defaults, $data);


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
            'biaya_lain',
            'sisa_hutang',
            'hutang',
            'bayar_hutang',
            'sisa_bayar'
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int)str_replace(['.', ','], ['', '.'], $data[$field]);
            }
        }
        // Hitung total
        if (isset($data['tonase']) && isset($data['harga_satuan'])) {
            $data['total'] = $data['tonase'] * $data['harga_satuan'];
        }

        // Hitung sisa hutang
        $data['sisa_hutang'] = $data['hutang'] - $data['bayar_hutang'];

        // Hitung sisa bayar dengan semua komponen
        $data['sisa_bayar'] = $data['total'] - $data['upah_bongkar'] - $data['biaya_lain'] - $data['bayar_hutang'];

        return $data;
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

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
