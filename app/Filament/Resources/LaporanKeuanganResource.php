<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKeuanganResource\Pages;
use App\Filament\Resources\LaporanKeuanganResource\RelationManagers;
use App\Models\LaporanKeuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LaporanKeuanganResource extends Resource
{
    protected static ?string $model = LaporanKeuangan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;

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

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\DateTimePicker::make('tanggal')
    //                 ->required(),
    //             Forms\Components\TextInput::make('jenis')
    //                 ->required(),
    //             Forms\Components\TextInput::make('tipe_transaksi')
    //                 ->required(),
    //             Forms\Components\TextInput::make('kategori_do'),
    //             Forms\Components\Select::make('kategori_operasional_id')
    //                 ->relationship('kategoriOperasional', 'id'),
    //             Forms\Components\TextInput::make('keterangan')
    //                 ->maxLength(255),
    //             Forms\Components\TextInput::make('nominal')
    //                 ->required()
    //                 ->numeric(),
    //             Forms\Components\TextInput::make('saldo_sebelum')
    //                 ->required()
    //                 ->numeric(),
    //             Forms\Components\TextInput::make('saldo_sesudah')
    //                 ->required()
    //                 ->numeric(),
    //             Forms\Components\Select::make('transaksi_do_id')
    //                 ->relationship('transaksiDo', 'id'),
    //             Forms\Components\Select::make('operasional_id')
    //                 ->relationship('operasional', 'id'),
    //             Forms\Components\TextInput::make('created_by')
    //                 ->numeric(),
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->dateTime()
                    ->sortable(),

                //tambah badges
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

                //tambah badges
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
                Tables\Columns\TextColumn::make('kategori_do'),
                Tables\Columns\TextColumn::make('kategoriOperasional.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nominal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_sebelum')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_sesudah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaksiDo.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operasional.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                // Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                // Tables\Actions\RestoreBulkAction::make(),
                // ]),
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
            'index' => Pages\ListLaporanKeuangans::route('/'),
            'create' => Pages\CreateLaporanKeuangan::route('/create'),
            'edit' => Pages\EditLaporanKeuangan::route('/{record}/edit'),
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
