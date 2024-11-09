<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Operasional;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\OperasionalResource\Pages;

class OperasionalResource extends Resource
{
    protected static ?string $model = Operasional::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Data Operasional';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('tanggal')
                            ->label('Tanggal')
                            ->timezone('Asia/Jakarta')
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('operasional')
                            ->label('Jenis Operasional')
                            ->options(Operasional::JENIS_OPERASIONAL)
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship(
                                name: 'kategori',
                                titleAttribute: 'nama',
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('keterangan')
                                    ->maxLength(255),
                            ])
                            ->required(),

                        Forms\Components\Select::make('tipe_nama')
                            ->label('Tipe')
                            ->options([
                                'penjual' => 'Penjual',
                                'pekerja' => 'Pekerja',
                                'user' => 'User/Kasir'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('penjual_id', null);
                                $set('pekerja_id', null);
                                $set('user_id', null);
                            }),

                        Forms\Components\Select::make('penjual_id')
                            ->label('Nama Penjual')
                            ->relationship('penjual', 'nama')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('alamat')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('telepon')
                                    ->maxLength(255),
                            ])
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'penjual'),

                        // Select untuk Pekerja dengan create option
                        Forms\Components\Select::make('pekerja_id')
                            ->label('Nama Pekerja')
                            ->relationship('pekerja', 'nama')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama')
                                    ->required()
                                    ->label('Nama'),
                                Forms\Components\TextInput::make('alamat')
                                    ->label('Alamat'),
                                Forms\Components\TextInput::make('telepon')
                                    ->label('Telepon/HP'),
                            ])
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'pekerja'),

                        // Select untuk User
                        Forms\Components\Select::make('user_id')
                            ->label('Nama User/Kasir')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Forms\Get $get) => $get('tipe_nama') === 'user'),

                        Forms\Components\TextInput::make('nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->currencyMask(
                                thousandSeparator: '.',
                                decimalSeparator: ',',
                                precision: 0,
                            ),

                        Forms\Components\TextInput::make('keterangan')
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\FileUpload::make('file_bukti')
                            ->label('Upload Bukti')
                            ->directory('bukti-operasional')
                            ->image()
                            ->imagePreviewHeight('250')
                            ->maxSize(2048),
                    ])
                    ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('operasional')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Operasional::JENIS_OPERASIONAL[$state])
                    ->color(fn(string $state): string => match ($state) {
                        'pengeluaran' => 'danger',
                        'pemasukan' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tipe_nama')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->formatStateUsing(function (Model $record) {
                        return match ($record->tipe_nama) {
                            'penjual' => $record->penjual?->nama,
                            'pekerja' => $record->pekerja?->nama,
                            'user' => $record->user?->name,
                            default => '-'
                        };
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where(function ($query) use ($search) {
                            $query->whereHas('penjual', fn($q) => $q->where('nama', 'like', "%{$search}%"))
                                ->orWhereHas('pekerja', fn($q) => $q->where('nama', 'like', "%{$search}%"))
                                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->color(
                        fn(string $state, Model $record): string =>
                        $record->operasional === 'pemasukan' ? 'success' : 'danger'
                    )
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),

                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\ImageColumn::make('file_bukti')
                    ->label('Bukti')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('operasional')
                    ->label('Jenis Operasional')
                    ->options([
                        'isi_saldo' => 'ISI SALDO',
                        'bahan_bakar' => 'BAHAN BAKAR',
                        'transportasi' => 'TRANSPORTASI',
                        'perawatan' => 'PERAWATAN',
                        'gaji' => 'GAJI',
                        'pinjaman' => 'PINJAMAN',
                    ])
                    ->multiple()
                    ->indicator('Jenis'),

                Filter::make('tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari_tanggal')
                                    ->label('Dari Tanggal'),
                                Forms\Components\DatePicker::make('sampai_tanggal')
                                    ->label('Sampai Tanggal'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari: ' . date('d/m/Y', strtotime($data['dari_tanggal']));
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai: ' . date('d/m/Y', strtotime($data['sampai_tanggal']));
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data operasional')
            ->emptyStateDescription('Silakan tambah data operasional baru dengan klik tombol di atas')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperasionals::route('/'),
            'create' => Pages\CreateOperasional::route('/create'),
            'edit' => Pages\EditOperasional::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
