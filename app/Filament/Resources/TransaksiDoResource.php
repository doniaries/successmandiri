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
                                        ->autofocus()
                                        ->relationship('penjual', 'nama')
                                        ->searchable()
                                        ->preload()
                                        ->live() // Make field reactive
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                // Get fresh hutang data
                                                $penjual = Penjual::find($state);
                                                if ($penjual) {
                                                    $hutang = $penjual->hutang;
                                                    $set('hutang', $hutang);
                                                    $set('sisa_hutang', $hutang); // Set initial sisa hutang

                                                    // Reset bayar hutang when penjual changes
                                                    $set('bayar_hutang', 0);
                                                }
                                            } else {
                                                // Reset related fields
                                                $set('hutang', 0);
                                                $set('sisa_hutang', 0);
                                                $set('bayar_hutang', 0);
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
                                        ->default('Lunas')
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
                    ->state(function (TransaksiDo $record): int {
                        return max(0, $record->hutang - $record->bayar_hutang);
                    }),

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
            ])
            ->emptyStateHeading('Belum ada data Transaksi DO')
            ->emptyStateDescription('Silakan tambah Transaksi DO baru dengan klik tombol di atas')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    //---------------------------------//
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

    // Helper methods untuk kalkulasi
    private static function formatCurrency($number): int
    {
        if (empty($number)) return 0;
        // Handle string format currency
        if (is_string($number)) {
            return (int) str_replace(['.', ','], ['', '.'], $number);
        }
        return (int) $number;
    }


    //-----------------------------//
    // Helper methods untuk kalkulasi
    private static function hitungTotal($state, Forms\Get $get, Forms\Set $set): void
    {
        // Format values
        $tonase = self::formatCurrency($get('tonase'));
        $hargaSatuan = self::formatCurrency($get('harga_satuan'));

        if ($tonase && $hargaSatuan) {
            // Calculate total
            $total = $tonase * $hargaSatuan;
            $set('total', $total);

            // Recalculate sisa bayar
            $upahBongkar = self::formatCurrency($get('upah_bongkar'));
            $biayaLain = self::formatCurrency($get('biaya_lain'));
            $bayarHutang = self::formatCurrency($get('bayar_hutang'));

            // Total - (Upah Bongkar + Biaya Lain + Bayar Hutang)
            $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
            $set('sisa_bayar', max(0, $sisaBayar));
        }
    }



    // ---------------------//

    // Perbaikan logika bayar hutang
    private static function hitungPembayaranHutang($state, Forms\Get $get, Forms\Set $set): void
    {
        // Format values
        $hutang = self::formatCurrency($get('hutang'));
        $bayarHutang = self::formatCurrency($state);

        // Validate bayar hutang
        if ($bayarHutang > $hutang) {
            $bayarHutang = $hutang;
            $set('bayar_hutang', $hutang);
            Notification::make()
                ->warning()
                ->title('Pembayaran disesuaikan')
                ->body("Pembayaran hutang disesuaikan dengan total hutang: Rp " . number_format($hutang))
                ->send();
        }

        // Update sisa hutang
        $sisaHutang = $hutang - $bayarHutang;
        $set('sisa_hutang', max(0, $sisaHutang));

        // Recalculate sisa bayar
        $total = self::formatCurrency($get('total'));
        $upahBongkar = self::formatCurrency($get('upah_bongkar'));
        $biayaLain = self::formatCurrency($get('biaya_lain'));

        // Sisa Bayar = Total - (Upah Bongkar + Biaya Lain + Bayar Hutang)
        $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
        $set('sisa_bayar', max(0, $sisaBayar));
    }


    private static function hitungSisaBayar($state, Forms\Get $get, Forms\Set $set): void
    {
        // Format values
        $total = self::formatCurrency($get('total'));
        $upahBongkar = self::formatCurrency($get('upah_bongkar'));
        $biayaLain = self::formatCurrency($get('biaya_lain'));
        $bayarHutang = self::formatCurrency($get('bayar_hutang'));

        // Sisa Bayar = Total - (Upah Bongkar + Biaya Lain + Bayar Hutang)
        $sisaBayar = $total - $upahBongkar - $biayaLain - $bayarHutang;
        $set('sisa_bayar', max(0, $sisaBayar));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Format numeric fields
        $numericFields = [
            'tonase',
            'harga_satuan',
            'upah_bongkar',
            'biaya_lain',
            'bayar_hutang'
        ];

        foreach ($numericFields as $field) {
            $data[$field] = self::formatCurrency($data[$field] ?? 0);
        }

        // Get fresh hutang from penjual
        if (!empty($data['penjual_id'])) {
            $penjual = Penjual::find($data['penjual_id']);
            if ($penjual) {
                $data['hutang'] = $penjual->hutang;

                // Revalidate bayar_hutang
                if ($data['bayar_hutang'] > $data['hutang']) {
                    $data['bayar_hutang'] = $data['hutang'];
                }
            }
        }

        // Calculate derived values
        $data['total'] = $data['tonase'] * $data['harga_satuan'];
        $data['sisa_hutang'] = max(0, $data['hutang'] - $data['bayar_hutang']);
        $data['sisa_bayar'] = max(0, $data['total'] - $data['upah_bongkar'] - $data['biaya_lain'] - $data['bayar_hutang']);

        return $data;
    }
}
