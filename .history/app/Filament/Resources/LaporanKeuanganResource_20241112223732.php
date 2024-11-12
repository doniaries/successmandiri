<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKeuanganResource\Pages;
// use App\Filament\Resources\LaporankeuanganStatWidget\Widgets\LaporankeuanganStatWidget;
use App\Models\LaporanKeuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanKeuanganResource extends Resource
{
    protected static ?string $model = LaporanKeuangan::class;

    // Navigation settings
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    // protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Laporan';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralModelLabel = 'Laporan Keuangan';
    protected static bool $shouldRegisterNavigation = true; // Tambahkan ini


    public static function getWidgets(): array //daftarkan widget di sini
    {
        return [
            LaporankeuanganStatWidget::class
        ];
    }

    // Navigation badges dan color
    public static function getNavigationBadge(): ?string
    {
        $totalCount = static::getModel()::count();
        $masuk = static::getModel()::where('jenis', 'masuk')->count();
        $keluar = static::getModel()::where('jenis', 'keluar')->count();

        return "{$masuk} Masuk | {$keluar} Keluar";
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $masuk = static::getModel()::where('jenis', 'masuk')->count();
        $keluar = static::getModel()::where('jenis', 'keluar')->count();

        return $masuk >= $keluar ? 'success' : 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\DateTimePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->displayFormat('d/m/Y H:i')
                                    ->disabled(),

                                Forms\Components\Select::make('jenis')
                                    ->label('Jenis')
                                    ->options([
                                        'masuk' => 'Pemasukan',
                                        'keluar' => 'Pengeluaran',
                                    ])
                                    ->disabled(),

                                Forms\Components\Select::make('tipe_transaksi')
                                    ->label('Tipe')
                                    ->options([
                                        'transaksi_do' => 'Transaksi DO',
                                        'operasional' => 'Operasional'
                                    ])
                                    ->disabled(),

                                Forms\Components\TextInput::make('kategori_do')
                                    ->label('Kategori DO')
                                    ->formatStateUsing(fn($state) => LaporanKeuangan::KATEGORI_DO[$state] ?? '-')
                                    ->visible(fn($get) => $get('tipe_transaksi') === 'transaksi_do')
                                    ->disabled(),

                                Forms\Components\Select::make('kategori_operasional_id')
                                    ->label('Kategori Operasional')
                                    ->relationship('kategoriOperasional', 'nama')
                                    ->visible(fn($get) => $get('tipe_transaksi') === 'operasional')
                                    ->disabled(),

                                Forms\Components\TextInput::make('keterangan')
                                    ->disabled(),

                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->numeric(),

                                Forms\Components\TextInput::make('saldo_sebelum')
                                    ->label('Saldo Sebelum')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->numeric(),

                                Forms\Components\TextInput::make('saldo_sesudah')
                                    ->label('Saldo Sesudah')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->numeric(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'masuk' => 'Pemasukan',
                        'keluar' => 'Pengeluaran',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tipe_transaksi')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'transaksi_do' => 'Transaksi DO',
                        'operasional' => 'Operasional',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'transaksi_do' => 'info',
                        'operasional' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('kategori')
                    ->formatStateUsing(function (Model $record) {
                        if ($record->tipe_transaksi === 'transaksi_do') {
                            return LaporanKeuangan::KATEGORI_DO[$record->kategori_do] ?? '-';
                        }
                        return $record->kategoriOperasional?->nama ?? '-';
                    }),

                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('No. Transaksi Disalin'),

                Tables\Columns\TextColumn::make('nama_penjual')
                    ->label('Nama Penjual')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nama Penjual Disalin')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->color(fn(Model $record): string => $record->jenis === 'masuk' ? 'success' : 'danger')
                    ->weight('bold'),

                // Tables\Columns\TextColumn::make('saldo_sesudah')
                //     ->label('Saldo')
                //     ->money('IDR')
                //     ->alignment('right')
                //     ->color('info')
                //     ->weight('bold'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('nama_penjual')
                    ->label('Penjual')
                    ->options(function () {
                        return LaporanKeuangan::whereNotNull('nama_penjual')
                            ->distinct()
                            ->pluck('nama_penjual', 'nama_penjual')
                            ->toArray();
                    })
                    ->multiple(),

                Tables\Filters\Filter::make('nomor_transaksi')
                    ->form([
                        Forms\Components\TextInput::make('nomor_transaksi')
                            ->label('Nomor Transaksi')
                            ->placeholder('Cari nomor transaksi...')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['nomor_transaksi'],
                            fn(Builder $query, $nomor): Builder => $query->where('nomor_transaksi', 'like', "%{$nomor}%")
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->afterFormFilled(function (Model $record) {
                        Notification::make()
                            ->title('Melihat Detail Transaksi')
                            ->body("Melihat detail transaksi #{$record->id}")
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada laporan keuangan')
            ->emptyStateDescription('Laporan keuangan akan terisi otomatis saat ada transaksi DO atau operasional')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->poll('60s') // Auto refresh setiap 60 detik
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh Data')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        Notification::make()
                            ->title('Data Berhasil Diperbarui')
                            ->success()
                            ->send();
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKeuangans::route('/'),
            'view' => Pages\ViewLaporanKeuangan::route('/{record}'),
        ];
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Transaksi Baru')
            ->success()
            ->body('Data transaksi berhasil dicatat')
            ->persistent()
            ->send();
    }

    protected function afterDelete(): void
    {
        Notification::make()
            ->title('Transaksi Dihapus')
            ->success()
            ->body('Data transaksi berhasil dihapus')
            ->send();
    }
}
