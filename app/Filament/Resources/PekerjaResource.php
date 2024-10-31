<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PekerjaResource\Pages;
use App\Filament\Resources\PekerjaResource\RelationManagers;
use App\Models\Pekerja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class PekerjaResource extends Resource
{
    protected static ?string $model = Pekerja::class;

    // protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('alamat')
                    ->maxLength(255),
                Forms\Components\TextInput::make('telepon')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('pendapatan')
                    ->disabled()
                    ->prefix('Rp. ')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->default(0),
                Forms\Components\TextInput::make('hutang')
                    ->disabled()
                    ->prefix('Rp. ')
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->default(0),
                // Forms\Components\TextInput::make('created_by')
                //     ->numeric(),
                // Forms\Components\TextInput::make('updated_by')
                //     ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('alamat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pendapatan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hutang')
                    ->money('IDR')
                    ->sortable(),

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
            ->defaultSort('created_at', 'desc')

            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPekerjas::route('/'),
            'create' => Pages\CreatePekerja::route('/create'),
            'edit' => Pages\EditPekerja::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
