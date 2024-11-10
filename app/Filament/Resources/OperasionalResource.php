<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Operasional;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use App\Models\KategoriOperasional;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OperasionalResource\Pages;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class OperasionalResource extends Resource
{
    protected static ?string $model = Operasional::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Data Operasional';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Informasi Dasar
                Forms\Components\Section::make('Informasi Dasar')
                    ->description('Masukkan informasi dasar operasional')
                    ->schema([
                        Forms\Components\DateTimePicker::make('tanggal')
                            ->label('Tanggal')
                            ->timezone('Asia/Jakarta')
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->required()
                            ->columnSpan('full'),

                        Forms\Components\Select::make('operasional')
                            ->label('Jenis Operasional')
                            ->options(Operasional::JENIS_OPERASIONAL)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('kategori_id', null);
                                $set('tipe_nama', null);
                                $set('penjual_id', null);
                                $set('user_id', null);
                            })
                            ->columnSpan('full'),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(function (Forms\Get $get) {
                                return KategoriOperasional::query()
                                    ->where('jenis', $get('operasional'))
                                    ->pluck('nama', 'id');
                            })
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn(Forms\Get $get) => filled($get('operasional')))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('jenis')
                                    ->options(KategoriOperasional::JENIS_KATEGORI)
                                    ->required(),
                                Forms\Components\TextInput::make('keterangan')
                                    ->maxLength(255),
                            ])
                            ->columnSpan('full'),
                    ])
                    ->columns(2),

                // Section 2: Detail Transaksi
                Forms\Components\Section::make('Detail Transaksi')
                    ->description('Pilih tipe dan nama terkait')
                    ->schema([
                        Forms\Components\Select::make('tipe_nama')
                            ->label('Tipe')
                            ->options([
                                'penjual' => 'Penjual',
                                'user' => 'Karyawan',
                                'pekerja' => 'Pekerja',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('penjual_id', null);
                                $set('user_id', null);
                                $set('pekerja_id', null);
                                $set('info_hutang', null);
                                $set('jumlah_hutang', 0);
                            })
                            ->columnSpan('full')
                            ->visible(fn(Forms\Get $get) => filled($get('kategori_id'))),

                        // Field Penjual
                        Forms\Components\Select::make('penjual_id')
                            ->label('Nama Penjual')
                            ->relationship('penjual', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('alamat')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('telepon')
                                    ->maxLength(255),
                            ])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $penjual = \App\Models\Penjual::find($state);
                                    if ($penjual) {
                                        $set('info_hutang', "Rp " . number_format($penjual->hutang, 0, ',', '.'));
                                        $set('jumlah_hutang', $penjual->hutang);
                                    }
                                } else {
                                    $set('info_hutang', null);
                                    $set('jumlah_hutang', 0);
                                }
                            })
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'penjual')
                            ->columnSpan('full'),

                        // Field Pekerja
                        Forms\Components\Select::make('pekerja_id')
                            ->label('Nama Pekerja')
                            ->relationship(
                                name: 'pekerja',
                                titleAttribute: 'nama',
                                modifyQueryUsing: fn(Builder $query) => $query->whereNull('deleted_at')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nama Pekerja'),
                                Forms\Components\TextInput::make('alamat')
                                    ->maxLength(255)
                                    ->label('Alamat'),
                                Forms\Components\TextInput::make('telepon')
                                    ->maxLength(255)
                                    ->label('No. Telepon'),
                                Forms\Components\TextInput::make('pendapatan')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->label('Pendapatan'),
                                Forms\Components\TextInput::make('hutang')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->label('Hutang'),
                            ])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $pekerja = \App\Models\Pekerja::find($state);
                                    if ($pekerja) {
                                        $set('info_hutang', "Rp " . number_format($pekerja->hutang, 0, ',', '.'));
                                        $set('jumlah_hutang', $pekerja->hutang);
                                    }
                                } else {
                                    $set('info_hutang', null);
                                    $set('jumlah_hutang', 0);
                                }
                            })
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'pekerja')
                            ->columnSpan('full'),

                        // Field User/Karyawan
                        Forms\Components\Select::make('user_id')
                            ->label('Nama Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'user')
                            ->columnSpan('full'),

                        // Informasi Hutang
                        Forms\Components\Placeholder::make('info_hutang')
                            ->label('Total Hutang Saat Ini')
                            ->content(fn(Forms\Get $get): string => $get('info_hutang') ?? 'Rp 0')
                            ->visible(
                                fn(Forms\Get $get) =>
                                in_array($get('tipe_nama'), ['penjual', 'pekerja']) &&
                                    (filled($get('penjual_id')) || filled($get('pekerja_id')))
                            )
                            ->extraAttributes([
                                'class' => 'text-2xl font-bold text-yellow-400',
                                'style' => 'text-transform: uppercase;'
                            ])
                            ->columnSpan('full'),
                    ])
                    ->columns(2),

                // Section 3: Nominal dan Bukti - Perbaikan kondisi visible
                Forms\Components\Section::make('Nominal dan Bukti')
                    ->description('Masukkan nominal dan upload bukti transaksi')
                    ->schema([
                        Forms\Components\TextInput::make('nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->currencyMask(
                                thousandSeparator: '.',
                                decimalSeparator: ',',
                                precision: 0,
                            )
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                if (
                                    $get('operasional') === 'pemasukan' &&
                                    $get('kategori_id') &&
                                    filled($get('jumlah_hutang'))
                                ) {
                                    $nominal = (int) str_replace(['.', ','], ['', '.'], $state);
                                    $hutang = (int) $get('jumlah_hutang');

                                    if ($nominal > $hutang) {
                                        $set('nominal', number_format($hutang, 0, ',', '.'));
                                        Notification::make()
                                            ->title('Nominal melebihi hutang')
                                            ->warning()
                                            ->body("Nominal pembayaran disesuaikan dengan total hutang Rp " . number_format($hutang, 0, ',', '.'))
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpan('full')
                            ->visible(
                                fn(Forms\Get $get) =>
                                filled($get('tipe_nama')) &&
                                    (
                                        filled($get('penjual_id')) ||
                                        filled($get('user_id')) ||
                                        filled($get('pekerja_id'))
                                    )
                            ),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Forms\Components\FileUpload::make('file_bukti')
                            ->label('Upload Bukti')
                            ->directory('bukti-operasional')
                            ->image()
                            ->imagePreviewHeight('250')
                            ->maxSize(2048)
                            ->columnSpan('full'),

                        Forms\Components\Hidden::make('is_from_transaksi')
                            ->default(false),

                        Forms\Components\Hidden::make('jumlah_hutang')
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('operasional')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Operasional::JENIS_OPERASIONAL[$state])
                    ->color(fn(string $state): string => match ($state) {
                        'pengeluaran' => 'danger',
                        'pemasukan' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipe_nama')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->formatStateUsing(function (Model $record) {
                        return match ($record->tipe_nama) {
                            'penjual' => $record->penjual?->nama,
                            'user' => $record->user?->name,
                            default => '-'
                        };
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where(function ($query) use ($search) {
                            $query->whereHas('penjual', fn($q) => $q->where('nama', 'like', "%{$search}%"))
                                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->color(
                        fn(string $state, Model $record): string =>
                        $record->operasional === 'pemasukan' ? 'success' : 'danger'
                    )
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),

                Tables\Columns\IconColumn::make('is_from_transaksi')
                    ->label('Dari DO')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(30)
                    ->searchable()
                    ->description(fn(Operasional $record) =>
                    $record->is_from_transaksi ? 'Data dari Transaksi DO' : null)
                    ->tooltip(fn(Operasional $record) => $record->keterangan),

                Tables\Columns\ImageColumn::make('file_bukti')
                    ->label('Bukti')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->latest('tanggal'))
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('operasional')
                    ->label('Jenis Operasional')
                    ->options([
                        'isi_saldo' => 'ISI SALDO',
                        'bahan_bakar' => 'BAHAN BAKAR',
                        'transportasi' => 'TRANSPORTASI',
                        'perawatan' => 'PERAWATAN',
                        'gaji' => 'GAJI',
                        'pinjaman' => 'PINJAMAN',
                    ])
                    ->multiple()
                    ->indicator('Jenis'),
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari_tanggal')
                                    ->label('Dari Tanggal'),
                                Forms\Components\DatePicker::make('sampai_tanggal')
                                    ->label('Sampai Tanggal'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari: ' . date('d/m/Y', strtotime($data['dari_tanggal']));
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai: ' . date('d/m/Y', strtotime($data['sampai_tanggal']));
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Operasional $record) => !$record->is_from_transaksi),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Operasional $record) => !$record->is_from_transaksi),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function (EloquentCollection $records) {  // Ubah tipe Collection
                        $records->reject(fn($record) => $record->is_from_transaksi)
                            ->each(fn($record) => $record->delete());
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Data berhasil dihapus')
                            ->body('Data yang bukan dari transaksi DO telah dihapus')
                    ),

                Tables\Actions\ForceDeleteBulkAction::make()
                    ->action(function (EloquentCollection $records) {  // Ubah tipe Collection
                        $records->reject(fn($record) => $record->is_from_transaksi)
                            ->each(fn($record) => $record->forceDelete());
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Data berhasil dihapus permanen')
                            ->body('Data yang bukan dari transaksi DO telah dihapus permanen')
                    ),

                Tables\Actions\RestoreBulkAction::make()
                    ->action(function (EloquentCollection $records) {  // Ubah tipe Collection
                        $records->reject(fn($record) => $record->is_from_transaksi)
                            ->each(fn($record) => $record->restore());
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Data berhasil dipulihkan')
                            ->body('Data yang bukan dari transaksi DO telah dipulihkan')
                    ),

            ])
            ->emptyStateHeading('Belum ada data operasional')
            ->emptyStateDescription('Silakan tambah data operasional baru dengan klik tombol di atas')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperasionals::route('/'),
            'create' => Pages\CreateOperasional::route('/create'),
            'edit' => Pages\EditOperasional::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
