<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Pinjam;
use App\Models\Pekerja;
use App\Models\Penjual;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\PinjamResource\Pages;

class PinjamResource extends Resource
{
    protected static ?string $model = Pinjam::class;

    protected static ?string $navigationLabel = 'Peminjaman';
    protected static ?string $modelLabel = 'Data Pinjaman';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false; //disable tombol navigasi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->schema([
                        Section::make('Informasi Utama')
                            ->description('Masukkan informasi dasar peminjaman')
                            ->icon('heroicon-o-information-circle')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('tanggal_pinjaman')
                                    ->label('Tanggal Pinjaman')
                                    ->required()
                                    ->default(now())
                                    ->columnSpan(1),

                                Select::make('kategori_peminjam')
                                    ->label('Kategori Peminjam')
                                    ->options([
                                        'Penjual' => 'Penjual',
                                        'Pekerja' => 'Pekerja',
                                    ])
                                    ->required()
                                    ->live()
                                    ->native(false)
                                    ->afterStateUpdated(fn(Forms\Set $set) => $set('peminjam_id', null))
                                    ->columnSpan(1),

                                Select::make('peminjam_id')
                                    ->label('Nama Peminjam')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->options(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');
                                        if (!$kategori) return [];

                                        return match ($kategori) {
                                            'Pekerja' => Pekerja::query()
                                                ->active()
                                                ->pluck('nama', 'id')
                                                ->toArray(),
                                            'Penjual' => Penjual::query()
                                                ->whereNull('deleted_at')
                                                ->pluck('nama', 'id')
                                                ->toArray(),
                                            default => [],
                                        };
                                    })
                                    ->columnSpan(1),

                                TextInput::make('nominal')
                                    ->label('Nominal Pinjaman')
                                    ->required()
                                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->step('0.01')
                                    ->columnSpan(1),
                                Textarea::make('deskripsi')
                                    ->label('Keterangan Pinjaman')
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->columnSpan(2),
                            ]),

                        Section::make('Informasi Peminjam')
                            ->description('Detail data peminjam yang dipilih')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Placeholder::make('nama')
                                    ->label('Nama Lengkap')
                                    ->content(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');
                                        $peminjamId = $get('peminjam_id');

                                        if (!$kategori || !$peminjamId) return '-';

                                        $peminjam = match ($kategori) {
                                            'Pekerja' => Pekerja::find($peminjamId),
                                            'Penjual' => Penjual::find($peminjamId),
                                            default => null,
                                        };

                                        return $peminjam?->nama ?? '-';
                                    }),

                                Placeholder::make('alamat')
                                    ->label('Alamat')
                                    ->content(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');
                                        $peminjamId = $get('peminjam_id');

                                        if (!$kategori || !$peminjamId) return '-';

                                        $peminjam = match ($kategori) {
                                            'Pekerja' => Pekerja::find($peminjamId),
                                            'Penjual' => Penjual::find($peminjamId),
                                            default => null,
                                        };

                                        return $peminjam?->alamat ?? '-';
                                    }),

                                Placeholder::make('telepon')
                                    ->label('No Telp/HP')
                                    ->content(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');
                                        $peminjamId = $get('peminjam_id');

                                        if (!$kategori || !$peminjamId) return '-';

                                        $peminjam = match ($kategori) {
                                            'Pekerja' => Pekerja::find($peminjamId),
                                            'Penjual' => Penjual::find($peminjamId),
                                            default => null,
                                        };

                                        return $peminjam?->telepon ?? '-';
                                    }),

                                Placeholder::make('hutang')
                                    ->label('Total Hutang')
                                    ->content(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');
                                        $peminjamId = $get('peminjam_id');

                                        if (!$kategori || !$peminjamId) return '-';

                                        $peminjam = match ($kategori) {
                                            'Pekerja' => Pekerja::find($peminjamId),
                                            'Penjual' => Penjual::find($peminjamId),
                                            default => null,
                                        };

                                        if (!$peminjam) return '-';

                                        return 'Rp. ' . number_format($peminjam->hutang, 0, ',', '.',);
                                    }),
                            ])
                            ->columns(2)
                            ->hidden(
                                fn(Forms\Get $get): bool =>
                                !$get('kategori_peminjam') ||
                                    !$get('peminjam_id')
                            ),

                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pinjaman')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('kategori_peminjam')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pekerja' => 'success',
                        'Penjual' => 'warning',
                        default => 'gray',
                    }),

                // Menggunakan accessor untuk nama peminjam
                TextColumn::make('nama_peminjam')
                    ->label('Nama Peminjam')
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($query) use ($search) {
                            $query->whereHas('pekerja', function ($query) use ($search) {
                                $query->where('nama', 'like', "%{$search}%");
                            })
                                ->orWhereHas('penjual', function ($query) use ($search) {
                                    $query->where('nama', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderBy(function ($query) {
                            $query->selectRaw('COALESCE(
                                (SELECT nama FROM pekerjas WHERE pekerjas.id = pinjams.peminjam_id AND pinjams.kategori_peminjam = "Pekerja"),
                                (SELECT nama FROM penjuals WHERE penjuals.id = pinjams.peminjam_id AND pinjams.kategori_peminjam = "Penjual")
                            )');
                        }, $direction);
                    }),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->formatStateUsing(
                        fn(string $state): string =>
                        'Rp ' . number_format((float) $state, 0, ',', '.')
                    )
                    ->sortable()
                    ->searchable(),

                TextColumn::make('deskripsi')
                    ->label('Keterangan')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_peminjam')
                    ->label('Kategori')
                    ->options([
                        'Penjual' => 'Penjual',
                        'Pekerja' => 'Pekerja'
                    ])
                    ->indicator('Kategori'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): string {
                        if (!$data) return '';
                        return 'Periode: ' . ($data['from'] ?? '') . ' - ' . ($data['until'] ?? '');
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->before(function (Model $record): void {
                            try {
                                DB::beginTransaction();

                                $peminjam = match ($record->kategori_peminjam) {
                                    'Pekerja' => Pekerja::find($record->peminjam_id),
                                    'Penjual' => Penjual::find($record->peminjam_id),
                                    default => null
                                };

                                if (!$peminjam) {
                                    throw new Exception('Data peminjam tidak ditemukan.');
                                }

                                $peminjam->hutang -= $record->nominal;
                                $peminjam->save();

                                DB::commit();
                            } catch (Exception $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal menghapus pinjaman')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->persistent()
                                    ->send();
                                throw $e;
                            }
                        })
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(function (Tables\Actions\DeleteBulkAction $action): void {
                            try {
                                DB::beginTransaction();

                                // Menggunakan getRecords() untuk mengakses records
                                foreach ($action->getRecords() as $record) {
                                    $peminjam = match ($record->kategori_peminjam) {
                                        'Pekerja' => Pekerja::find($record->peminjam_id),
                                        'Penjual' => Penjual::find($record->peminjam_id),
                                        default => null
                                    };

                                    if (!$peminjam) {
                                        throw new Exception("Data peminjam tidak ditemukan untuk ID: {$record->peminjam_id}");
                                    }

                                    $peminjam->hutang -= $record->nominal;
                                    $peminjam->save();
                                }

                                DB::commit();
                            } catch (Exception $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal menghapus pinjaman')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->persistent()
                                    ->send();
                                throw $e;
                            }
                        })
                ]),
            ])
            ->emptyStateHeading('Belum ada data pinjaman')
            ->emptyStateDescription('Silakan buat data pinjaman baru dengan klik tombol di atas')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPinjams::route('/'),
            'create' => Pages\CreatePinjam::route('/create'),
            'edit' => Pages\EditPinjam::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
