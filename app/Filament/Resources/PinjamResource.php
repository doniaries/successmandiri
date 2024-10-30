<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PinjamResource\Pages;
use App\Models\Pinjam;
use App\Models\Pekerja;
use App\Models\Penjual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PinjamResource extends Resource
{
    protected static ?string $model = Pinjam::class;

    protected static ?string $navigationLabel = 'Peminjaman';
    protected static ?string $modelLabel = 'Data Pinjaman';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->schema([
                        Forms\Components\Section::make('Informasi Utama')
                            ->description('Masukkan informasi dasar peminjaman')
                            ->icon('heroicon-o-information-circle')
                            ->columns(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal_pinjaman')
                                    ->label('Tanggal Pinjaman')
                                    ->required()
                                    ->default(now())
                                    ->columnSpan(1),

                                Forms\Components\Select::make('kategori_peminjam')
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

                                Forms\Components\Select::make('peminjam_id')
                                    ->label('Nama Peminjam')
                                    ->required()
                                    ->native(false)
                                    ->options(function (Forms\Get $get) {
                                        $kategori = $get('kategori_peminjam');

                                        if (!$kategori) {
                                            return [];
                                        }

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

                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal Pinjaman')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->mask('999.999.999')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Section::make('Keterangan')
                            ->schema([
                                Forms\Components\Textarea::make('deskripsi')
                                    ->label('Keterangan Pinjaman')
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pinjaman')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori_peminjam')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'karyawan' => 'success',
                        'nasabah' => 'warning',
                        'umum' => 'danger',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('peminjam_id')
                    ->label('ID Peminjam')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('idr')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Keterangan')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
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
                        'karyawan' => 'Karyawan',
                        'nasabah' => 'Nasabah',
                        'umum' => 'Umum'
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
                    ->indicator('Periode'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data pinjaman')
            ->emptyStateDescription('Silakan buat data pinjaman baru dengan klik tombol di atas')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s'); // Auto refresh setiap 1 menit
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