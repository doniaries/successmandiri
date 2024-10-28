<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiDoResource\Pages;
use App\Filament\Resources\TransaksiDoResource\RelationManagers;
use App\Models\TransaksiDo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;

class TransaksiDoResource extends Resource
{
    protected static ?string $model = TransaksiDo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('tanggal')
                    ->default(now())
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('penjual_id')
                    ->relationship('penjual', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('nomor_polisi')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tonase')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                MoneyInput::make('harga_satuan')
                    ->required()
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->default(0.00),
                MoneyInput::make('upah_bongkar')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0),
                MoneyInput::make('hutang')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0),
                MoneyInput::make('bayar_hutang')
                    ->currency('IDR')
                    ->locale('id_ID')
                    ->default(0),
                Forms\Components\TextInput::make('sisa_bayar')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('file_do')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cara_bayar')
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('penjual_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_polisi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tonase')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_satuan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('upah_bongkar')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hutang')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bayar_hutang')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_do')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cara_bayar'),
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
}