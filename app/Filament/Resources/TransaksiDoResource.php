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
            Forms\Components\Section::make()
                ->schema([
                    // Header Information
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('nomor')
                                ->label('Nomor DO')
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
                ]),

            Forms\Components\Section::make()
                ->schema([
                    // Penjual Selection & Basic Info
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Select::make('penjual_id')
                                ->label('Penjual')
                                ->relationship('penjual', 'nama')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, ?Model $record) { // Tambah $record parameter
                                    if ($state) {
                                        $penjual = Penjual::find($state);
                                        if ($penjual) {
                                            // Check record melalui parameter, bukan $this
                                            if (!$record) {
                                                $set('hutang_awal', $penjual->hutang);
                                                $set('sisa_hutang_penjual', $penjual->hutang);
                                            }
                                            $set('info_hutang_terkini', $penjual->hutang);
                                        }
                                    } else {
                                        $set('hutang_awal', 0);
                                        $set('sisa_hutang_penjual', 0);
                                        $set('info_hutang_terkini', 0);
                                    }
                                })
                                ->required(),
                            Forms\Components\TextInput::make('nomor_polisi')
                                ->placeholder('BA 1234 K')
                                ->label('Nomor Polisi'),
                        ])
                        ->columns(2),

                    // Basic Transaction Details
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('tonase')
                                ->label('Tonase (Netto)')
                                ->required()
                                ->numeric()
                                ->suffix('Kg')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    if ($state && $get('harga_satuan')) {
                                        $total = $state * $get('harga_satuan');
                                        $set('total', $total);
                                        static::updateSisaBayar($get, $set);
                                    }
                                }),

                            Forms\Components\TextInput::make('harga_satuan')
                                ->label('Harga Satuan')
                                ->required()
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    if ($state && $get('tonase')) {
                                        $total = $state * $get('tonase');
                                        $set('total', $total);
                                        static::updateSisaBayar($get, $set);
                                    }
                                }),

                            Forms\Components\TextInput::make('total')
                                ->label('Sub Total')
                                ->prefix('Rp')
                                ->default(0)
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->disabled()
                                ->dehydrated()
                        ])
                        ->columns(3),

                    // Payment Details
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('upah_bongkar')
                                ->label('Upah Bongkar')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                static::updateSisaBayar($get, $set)),

                            Forms\Components\TextInput::make('biaya_lain')
                                ->label('Biaya Lain')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                static::updateSisaBayar($get, $set)),

                            // Informasi Hutang - Read Only
                            Forms\Components\TextInput::make('hutang_awal')
                                ->label('Hutang Awal')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->disabled()
                                ->dehydrated(),

                            // Hidden field untuk info hutang terkini
                            Forms\Components\Hidden::make('info_hutang_terkini'),

                            // Placeholder untuk menampilkan hutang terkini
                            Forms\Components\Placeholder::make('hutang_terkini_info')
                                ->label('Hutang Terkini')
                                ->content(fn(Forms\Get $get): string =>
                                'Rp ' . number_format($get('info_hutang_terkini'), 0, ',', '.'))
                                ->visible(fn(Forms\Get $get): bool =>
                                (bool)$get('penjual_id')),

                            Forms\Components\TextInput::make('pembayaran_hutang')
                                ->label('Pembayaran Hutang')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $hutangAwal = $get('hutang_awal');

                                    // Validasi pembayaran tidak melebihi hutang
                                    if ($state > $hutangAwal) {
                                        $set('pembayaran_hutang', $hutangAwal);
                                        $state = $hutangAwal;
                                        Notification::make()
                                            ->warning()
                                            ->title('Pembayaran disesuaikan')
                                            ->body('Pembayaran hutang tidak boleh melebihi hutang awal')
                                            ->send();
                                    }

                                    // Update sisa hutang
                                    $set('sisa_hutang_penjual', max(0, $hutangAwal - $state));

                                    // Update sisa bayar
                                    static::updateSisaBayar($get, $set);
                                }),

                            Forms\Components\TextInput::make('sisa_hutang_penjual')
                                ->label('Sisa Hutang')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('sisa_bayar')
                                ->prefix('Rp')
                                ->currencyMask(
                                    thousandSeparator: ',',
                                    decimalSeparator: '.',
                                    precision: 0
                                )
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3),

                    // Payment Method & Status
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Select::make('cara_bayar')
                                ->label('Cara Bayar')
                                ->options([
                                    'Tunai' => 'Tunai',
                                    'Transfer' => 'Transfer',
                                ])
                                ->default('Tunai')
                                ->required(),

                            Forms\Components\Select::make('status_bayar')
                                ->label('Status Bayar')
                                ->options([
                                    'Lunas' => 'Lunas',
                                    'Belum Lunas' => 'Belum Lunas',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('catatan')
                                ->label('Catatan')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
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


                Tables\Columns\TextColumn::make('hutang_awal')
                    ->label('Hutang')
                    ->money('IDR')
                    ->color(Color::Red)
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembayaran_hutang')
                    ->label('Bayar Hutang')
                    ->money('IDR')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->color(Color::Orange)
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_hutang_penjual')
                    ->label('Sisa Hutang')
                    ->money('IDR')
                    ->color('danger')
                    ->alignment('right')
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
            ->poll('5s')
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

    // Helper method untuk update sisa bayar
    private static function updateSisaBayar(Forms\Get $get, Forms\Set $set): void
    {
        $total = $get('total') ?? 0;
        $upahBongkar = $get('upah_bongkar') ?? 0;
        $biayaLain = $get('biaya_lain') ?? 0;
        $pembayaranHutang = $get('pembayaran_hutang') ?? 0;

        $sisaBayar = $total - $upahBongkar - $biayaLain - $pembayaranHutang;
        $set('sisa_bayar', max(0, $sisaBayar));
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
            $bayarHutang = self::formatCurrency($get('pembayaran_hutang'));

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
            $set('pembayaran_hutang', $hutang);
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
        $bayarHutang = self::formatCurrency($get('pembayaran_hutang'));

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
            'pembayaran_hutang'
        ];

        foreach ($numericFields as $field) {
            $data[$field] = self::formatCurrency($data[$field] ?? 0);
        }

        // Get fresh hutang from penjual
        if (!empty($data['penjual_id'])) {
            $penjual = Penjual::find($data['penjual_id']);
            if ($penjual) {
                $data['hutang'] = $penjual->hutang;

                // Revalidate pembayaran_hutang
                if ($data['pembayaran_hutang'] > $data['hutang']) {
                    $data['pembayaran_hutang'] = $data['hutang'];
                }
            }
        }

        // Calculate derived values
        $data['total'] = $data['tonase'] * $data['harga_satuan'];
        $data['sisa_hutang'] = max(0, $data['hutang'] - $data['pembayaran_hutang']);
        $data['sisa_bayar'] = max(0, $data['total'] - $data['upah_bongkar'] - $data['biaya_lain'] - $data['pembayaran_hutang']);

        return $data;
    }
}
