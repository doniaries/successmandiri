<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriOperasionalResource\Pages;
use App\Filament\Resources\KategoriOperasionalResource\RelationManagers;
use App\Models\KategoriOperasional;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KategoriOperasionalResource extends Resource
{
    protected static ?string $model = KategoriOperasional::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('jenis')
                            ->options(KategoriOperasional::JENIS_KATEGORI)
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('keterangan')
                            ->maxLength(255)
                            ->columnSpan('full'),

                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pengeluaran' => 'danger',
                        'pemasukan' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(50),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
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
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoriOperasionals::route('/'),
            'create' => Pages\CreateKategoriOperasional::route('/create'),
            'edit' => Pages\EditKategoriOperasional::route('/{record}/edit'),
        ];
    }
}
