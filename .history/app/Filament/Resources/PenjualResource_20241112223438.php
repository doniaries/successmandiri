<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Penjual;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PenjualResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PenjualResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;

class PenjualResource extends Resource
{
    protected static ?string $model = Penjual::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    // protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3;




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Penjual')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('alamat')
                            ->label('Alamat')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telepon')
                            ->label('Nomor Telepon/HP')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('hutang')
                            ->label('total_hutang')
                            // ->disabled()
                            ->dehydrated()
                            ->prefix('Rp.')
                            ->numeric()
                            ->default(0)
                            ->currencyMask(
                                thousandSeparator: ',',
                                decimalSeparator: '.',
                                precision: 0
                            ),
                    ])
                    ->columns(2)
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
                Tables\Columns\TextColumn::make('hutang')
                    ->label('Hutang')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'info' : 'success')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('riwayat_bayar')
                    ->alignCenter()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('tambah_pinjaman')
                    ->label('Tambah Pinjaman')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal Pinjaman')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(
                                        thousandSeparator: ',',
                                        decimalSeparator: '.',
                                        precision: 2,
                                    ),

                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, Penjual $record): void {
                        DB::beginTransaction();

                        try {
                            // Update hutang penjual
                            $record->hutang += (float) str_replace([',', '.'], ['', '.'], $data['nominal']);
                            $record->save();

                            // Simpan ke tabel operasional
                            DB::table('operasional')->insert([
                                'tanggal' => $data['tanggal'],
                                'operasional' => 'pinjaman',
                                'atas_nama' => $record->nama,
                                'nominal' => str_replace([',', '.'], ['', '.'], $data['nominal']),
                                'keterangan' => $data['keterangan'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil menambahkan pinjaman')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Gagal menambahkan pinjaman')
                                ->danger()
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->modalHeading('Tambah Pinjaman Baru')
                    ->modalDescription(fn(Penjual $record) => "Menambahkan pinjaman untuk {$record->nama}")
                    ->modalSubmitActionLabel('Simpan Pinjaman')
                    ->requiresConfirmation(),
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
            RelationManagers\RiwayatHutangRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjuals::route('/'),
            'create' => Pages\CreatePenjual::route('/create'),
            'edit' => Pages\EditPenjual::route('/{record}/edit'),
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
}
