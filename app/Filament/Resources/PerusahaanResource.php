<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Perusahaan;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PerusahaanResource\Pages;
use Illuminate\Database\Eloquent\Factories\Relationship;
use App\Filament\Resources\PerusahaanResource\RelationManagers;
use Carbon\Carbon;

class PerusahaanResource extends Resource
{
    protected static ?string $model = Perusahaan::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('alamat')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pimpinan')
                    ->maxLength(255),
                Forms\Components\Select::make('kasir_id')
                    ->label('Kasir')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable(),

                Forms\Components\Toggle::make('is_active')
                    ->required(),

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
                Tables\Columns\TextColumn::make('pimpinan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('dibuat pada')
                    ->dateTime(Carbon::now('Asia/Jakarta'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('diperbarui pada')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
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
                Tables\Actions\DeleteAction::make(),
                Action::make('saldo')
                    ->label('Tambah Saldo')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading('Tambah Saldo Perusahaan')
                    ->modalDescription('Masukkan jumlah saldo yang akan ditambahkan')
                    ->form([
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        DatePicker::make('tanggal')
                                            ->label('Tanggal Tambah Saldo')
                                            ->default(now())
                                            ->required(),

                                        TextInput::make('nominal')
                                            ->label('Nominal Tambah Saldo')
                                            ->numeric()
                                            ->required()
                                            ->currencyMask(
                                                thousandSeparator: '.',
                                                decimalSeparator: ',',
                                                precision: 0,
                                            )
                                            ->prefix('Rp')
                                            ->placeholder('Masukkan nominal Tambah Saldo'),

                                        Textarea::make('keterangan')
                                            ->label('Keterangan')
                                            ->placeholder('Masukkan Keterangan')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->columns(1)
                            ])
                    ])
                    ->action(function (Perusahaan $record, array $data): void {
                        try {
                            DB::beginTransaction();

                            // Update saldo perusahaan
                            $record->increment('saldo', $data['nominal']);

                            // Catat di tabel operasional
                            DB::table('operasional')->insert([
                                'tanggal' => $data['tanggal'],
                                'operasional' => 'isi_saldo',
                                'atas_nama' => $record->nama,
                                'nominal' => $data['nominal'],
                                'keterangan' => $data['keterangan'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Berhasil Top Up')
                                ->body('Saldo berhasil ditambahkan sebesar Rp ' . number_format($data['nominal'], 0, ',', '.'))
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Gagal Tambah Saldo')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalButton('Tambah Saldo')
                    ->visible(fn(Perusahaan $record): bool => $record->is_active),
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
            'index' => Pages\ListPerusahaans::route('/'),
            'create' => Pages\CreatePerusahaan::route('/create'),
            'edit' => Pages\EditPerusahaan::route('/{record}/edit'),
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
