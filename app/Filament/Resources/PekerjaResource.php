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
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PekerjaResource extends Resource
{
    protected static ?string $model = Pekerja::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Master Data';

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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pekerja')
                    ->schema([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap'),
                        TextEntry::make('alamat'),
                        TextEntry::make('telepon')
                            ->label('No. Telepon'),
                        TextEntry::make('pendapatan')
                            ->money('IDR')
                            ->color('success'),
                        TextEntry::make('hutang')
                            ->money('IDR')
                            ->color('danger'),
                        TextEntry::make('created_at')
                            ->label('Terdaftar Pada')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PekerjaResource\RelationManagers\TransaksiDoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPekerjas::route('/'),
            'create' => Pages\CreatePekerja::route('/create'),
            'view' => Pages\ViewPekerja::route('/{record}'),
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
