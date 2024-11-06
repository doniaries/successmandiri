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
use Filament\Tables\Columns\ColorColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiDoResource\Pages;
use App\Filament\Resources\TransaksiDoResource\Widgets\TransaksiDOWidget;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\CreateAction;

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
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
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
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
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
                                        ->default(0)
                                        // ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungSisaBayar($state, $get, $set)),

                                    Forms\Components\Section::make('Pekerja')
                                        ->schema([
                                            Forms\Components\Select::make('pekerja_ids')
                                                ->label('Pilih Pekerja')
                                                ->multiple()
                                                ->relationship(
                                                    name: 'pekerjas',
                                                    titleAttribute: 'nama',
                                                    modifyQueryUsing: fn($query) => $query->whereNull('deleted_at')
                                                )
                                                ->preload()
                                                ->searchable()
                                                // ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                    $upahBongkar = $get('upah_bongkar') ?? 0;
                                                    $jumlahPekerja = count($state ?? []);

                                                    if ($jumlahPekerja > 0) {
                                                        $pendapatanPerPekerja = $upahBongkar / $jumlahPekerja;
                                                        $set('pendapatan_per_pekerja', $pendapatanPerPekerja);
                                                    }
                                                }),

                                            Forms\Components\TextInput::make('pendapatan_per_pekerja')
                                                ->label('Pendapatan Per Pekerja')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefix('Rp')
                                                ->numeric()
                                                ->mask(fn($get) => $get('upah_bongkar') ? number_format($get('upah_bongkar') / max(1, count($get('pekerja_ids') ?? [])), 0, ',', '.') : '0'),
                                        ])
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('bayar_hutang')
                                        ->label('Bayar Hutang')
                                        ->default(0)
                                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                        ->prefix('Rp')
                                        // ->required()
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
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) =>
                                        static::hitungPembayaranHutang($state, $get, $set)),

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
            ->headerActions([
                CreateAction::make()
                    ->label('Buat')
                    ->icon('heroicon-m-plus')
                    ->size('lg')
                    ->color('secondary'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('file_do') //image file do
                    ->label('File DO')
                    ->tooltip('klik untuk melihat')
                    ->alignCenter()
                    ->icon('heroicon-m-document')
                    ->iconPosition(IconPosition::Before)
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
                    ->color(Color::Emerald)
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('pekerjas.nama')
                    ->label('Pekerja')
                    ->badge()
                    ->separator(',')
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->label('Upah Bongkar')
                    ->money('IDR')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable(),

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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                //     Tables\Actions\ForceDeleteBulkAction::make(),
                //     Tables\Actions\RestoreBulkAction::make(),
                // ]),
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
        // Set default values for any potentially null fields
        $defaults = [
            'nomor_polisi' => '',
            'catatan' => '',
            'bayar_hutang' => 0,
            'hutang' => 0,
            'sisa_hutang' => 0,
            'sisa_bayar' => 0,
            'upah_bongkar' => 0,
        ];

        $data = array_merge($defaults, $data);

        // Clean numeric values
        $numericFields = [
            'tonase',
            'harga_satuan',
            'total',
            'upah_bongkar',
            'hutang',
            'bayar_hutang',
            'sisa_hutang',
            'sisa_bayar'
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                // Remove thousand separators and convert decimal separator
                $value = str_replace(['.', ','], ['', '.'], $data[$field]);
                // Convert to integer, defaulting to 0 if empty
                $data[$field] = $value !== '' ? (int)$value : 0;
            } else {
                $data[$field] = 0;
            }
        }

        // Ensure sisa_hutang is calculated correctly
        $hutang = $data['hutang'] ?? 0;
        $bayarHutang = $data['bayar_hutang'] ?? 0;
        $data['sisa_hutang'] = $hutang - $bayarHutang;

        // Calculate total if not set
        if (!isset($data['total'])) {
            $data['total'] = ($data['tonase'] ?? 0) * ($data['harga_satuan'] ?? 0);
        }

        // Calculate sisa_bayar
        $data['sisa_bayar'] = ($data['total'] ?? 0) - ($data['upah_bongkar'] ?? 0) - ($data['bayar_hutang'] ?? 0);

        return $data;
    }

    public static function getModel(): string
    {
        return TransaksiDo::class;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);

        if (isset($data['pekerja_ids']) && is_array($data['pekerja_ids']) && count($data['pekerja_ids']) > 0) {
            $upahPerPekerja = $data['upah_bongkar'] / count($data['pekerja_ids']);

            // Attach pekerja dengan pendapatan
            foreach ($data['pekerja_ids'] as $pekerjaId) {
                $record->pekerjas()->attach($pekerjaId, [
                    'pendapatan_pekerja' => $upahPerPekerja
                ]);

                // Update total pendapatan pekerja
                $pekerja = Pekerja::find($pekerjaId);
                if ($pekerja) {
                    $totalPendapatan = $pekerja->transaksiDos()
                        ->whereNull('deleted_at')
                        ->sum('pekerja_transaksi_do.pendapatan_pekerja');
                    $pekerja->update(['pendapatan' => $totalPendapatan]);
                }
            }
        }

        return $record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Rollback pendapatan lama
        $record->pekerjas->each(function ($pekerja) use ($record) {
            $pendapatanLama = $pekerja->pivot->pendapatan_pekerja;
            $pekerja->decrement('pendapatan', $pendapatanLama);
        });

        // Update record
        $record->update($data);

        // Update pendapatan baru
        if (isset($data['pekerja_ids']) && count($data['pekerja_ids']) > 0) {
            $upahPerPekerja = $data['upah_bongkar'] / count($data['pekerja_ids']);

            // Detach semua pekerja dulu
            $record->pekerjas()->detach();

            foreach ($data['pekerja_ids'] as $pekerjaId) {
                $pekerja = Pekerja::find($pekerjaId);
                if ($pekerja) {
                    // Attach dengan data baru
                    $record->pekerjas()->attach($pekerjaId, [
                        'pendapatan_pekerja' => $upahPerPekerja
                    ]);

                    // Update total pendapatan
                    $pekerja->increment('pendapatan', $upahPerPekerja);
                }
            }
        }

        return $record;
    }

    protected function handleRecordDeletion(Model $record): void
    {
        // Rollback pendapatan sebelum delete
        $record->pekerjas->each(function ($pekerja) {
            $pendapatan = $pekerja->pivot->pendapatan_pekerja;
            $pekerja->decrement('pendapatan', $pendapatan);
        });

        $record->delete();
    }

    public function afterCreate(): void
    {
        // Update pendapatan pekerja
        if (isset($this->record) && $this->record->pekerjas()->count() > 0) {
            $upahPerPekerja = $this->record->upah_bongkar / $this->record->pekerjas()->count();

            $this->record->pekerjas()->each(function ($pekerja) use ($upahPerPekerja) {
                $pekerja->increment('pendapatan', $upahPerPekerja);
            });
        }
    }

    public function afterDelete(): void
    {
        // Rollback pendapatan pekerja
        if (isset($this->record)) {
            $upahPerPekerja = $this->record->upah_bongkar / max(1, $this->record->pekerjas()->count());

            $this->record->pekerjas()->each(function ($pekerja) use ($upahPerPekerja) {
                $pekerja->decrement('pendapatan', $upahPerPekerja);
            });
        }
    }
}