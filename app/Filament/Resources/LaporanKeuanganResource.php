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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('tanggal')
                    ->required(),
                Forms\Components\TextInput::make('jenis_transaksi')
                    ->required(),
                Forms\Components\TextInput::make('kategori')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('sub_kategori')
                    ->maxLength(50),
                Forms\Components\TextInput::make('nominal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sumber_transaksi')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('referensi_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('nomor_referensi')
                    ->maxLength(50),
                Forms\Components\TextInput::make('pihak_terkait')
                    ->maxLength(100),
                Forms\Components\TextInput::make('tipe_pihak'),
                Forms\Components\TextInput::make('cara_pembayaran')
                    ->maxLength(20),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->dateTime()
                    ->dateTime('d/M/Y H:i')
                    ->label('Tanggal Transaksi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_transaksi')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sub_kategori')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nominal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sumber_transaksi')
                    ->label('dari')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referensi_id')
                    ->numeric()
                    ->hidden()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_referensi')
                    ->label('Nomor DO')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Nomor DO berhasil disalin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pihak_terkait')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipe_pihak'),
                Tables\Columns\TextColumn::make('cara_pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->deferLoading()
            ->poll('5s');
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

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
