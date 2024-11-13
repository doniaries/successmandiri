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
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiDoResource\Pages;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDoStatWidget;


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

    public static function getWidgets(): array
    {
        return [
            TransaksiDoStatWidget::class,
        ];
    }

    public static function getModel(): string
    {
        return TransaksiDo::class;
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    // KOLOM KIRI - Form Input (Width: 2/3)
                    Forms\Components\Section::make('Form Input Transaksi')
                        ->description('Masukkan data transaksi DO')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            // Header Information
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('nomor')
                                        ->label('Nomor DO')
                                        ->default(fn() => TransaksiDo::generateMonthlyNumber())
                                        ->disabled()
                                        ->extraAttributes(['class' => 'bg-gray-100']),

                                    Forms\Components\DateTimePicker::make('tanggal')
                                        ->label('Tanggal')
                                        ->timezone('Asia/Jakarta')
                                        ->displayFormat('d/m/Y H:i')
                                        ->default(now())
                                        ->disabled()
                                        ->required(),
                                ])
                                ->columns(2),

                            // Penjual & Kendaraan Information
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('penjual_id')
                                        ->label('Pilih Penjual')
                                        ->relationship('penjual', 'nama')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nama')->required(),
                                            Forms\Components\TextInput::make('alamat'),
                                            Forms\Components\TextInput::make('telepon')
                                        ])
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $penjual = \App\Models\Penjual::find($state);
                                                if ($penjual) {
                                                    $set('hutang_awal', $penjual->hutang);
                                                    $set('info_hutang_terkini', $penjual->hutang);
                                                }
                                            }
                                        }),

                                    Forms\Components\TextInput::make('nomor_polisi')
                                        ->label('Nomor Polisi')
                                        ->placeholder('BA 1234 K'),
                                ])
                                ->columns(2),

                            // Detail Transaksi
                            Forms\Components\Card::make()
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('tonase')
                                                ->label('Tonase (Netto)')
                                                ->required()
                                                ->numeric()
                                                ->suffix('Kg')
                                                ->live(onBlur: true),

                                            Forms\Components\TextInput::make('harga_satuan')
                                                ->label('Harga Satuan')
                                                ->required()
                                                ->prefix('Rp')
                                                ->numeric()
                                                ->live(onBlur: true),
                                        ])
                                        ->columns(2),

                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('upah_bongkar')
                                                ->label('Upah Bongkar')
                                                ->prefix('Rp')
                                                ->default(0)
                                                ->live(onBlur: true),

                                            Forms\Components\TextInput::make('biaya_lain')
                                                ->label('Biaya Lain')
                                                ->prefix('Rp')
                                                ->default(0)
                                                ->live(onBlur: true),

                                            Forms\Components\TextInput::make('pembayaran_hutang')
                                                ->label('Pembayaran Hutang')
                                                ->prefix('Rp')
                                                ->default(0)
                                                ->live(onBlur: true),
                                        ])
                                        ->columns(3),
                                ]),

                            // Pembayaran & Status
                            Forms\Components\Card::make()
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\Select::make('cara_bayar')
                                                ->label('Cara Bayar')
                                                ->options([
                                                    'Tunai' => 'Tunai',
                                                    'Transfer' => 'Transfer',
                                                    'Cair di Luar' => 'Cair di Luar'
                                                ])
                                                ->default('Tunai')
                                                ->required(),

                                            Forms\Components\Select::make('status_bayar')
                                                ->label('Status Bayar')
                                                ->options([
                                                    'Lunas' => 'Lunas',
                                                    'Belum Lunas' => 'Belum Lunas'
                                                ])
                                                ->required(),
                                        ])
                                        ->columns(2),

                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\FileUpload::make('file_do')
                                                ->label('Upload File DO')
                                                ->directory('do-files')
                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                ->maxSize(5120),

                                            Forms\Components\Textarea::make('catatan')
                                                ->label('Catatan')
                                                ->rows(2),
                                        ])
                                        ->columns(2),
                                ]),
                        ])
                        ->columnSpan(2),

                    // KOLOM KANAN - Summary (Width: 1/3)
                    Forms\Components\Section::make('Informasi Transaksi')
                        ->description('Ringkasan transaksi dan hutang')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            // Hidden Fields
                            Forms\Components\Hidden::make('hutang_awal'),
                            Forms\Components\Hidden::make('info_hutang_terkini'),

                            // Summary Cards
                            Forms\Components\Card::make()
                                ->schema([
                                    Forms\Components\TextInput::make('total')
                                        ->label('TOTAL TRANSAKSI')
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600']),

                                    Forms\Components\TextInput::make('info_hutang_terkini')
                                        ->label('TOTAL HUTANG')
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->extraAttributes(['class' => 'font-bold text-danger-500']),

                                    Forms\Components\TextInput::make('sisa_hutang')
                                        ->label('SISA HUTANG')
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->extraAttributes(['class' => 'font-bold text-warning-500']),

                                    Forms\Components\TextInput::make('sisa_bayar')
                                        ->label('SISA BAYAR')
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->extraAttributes(['class' => 'font-bold text-success-500']),
                                ])
                        ])
                        ->columnSpan(1)
                        ->extraAttributes(['class' => 'sticky top-0']),
                ])
                ->columns(3)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor DO')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor DO disalin'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penjual.nama')
                    ->label('Penjual')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_polisi')
                    ->label('No. Polisi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tonase')
                    ->label('Tonase')
                    ->suffix(' Kg')
                    ->numeric(
                        decimalPlaces: 0,
                        decimalSeparator: ',',
                        thousandsSeparator: '.'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),

                Tables\Columns\TextColumn::make('status_bayar')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            ])
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Silakan tambah transaksi baru');
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['penjual'])
            ->latest();
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiDos::route('/'),
            'create' => Pages\CreateTransaksiDo::route('/create'),
            'edit' => Pages\EditTransaksiDo::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }

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
