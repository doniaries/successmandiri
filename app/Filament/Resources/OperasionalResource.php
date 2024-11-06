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
                        Forms\Components\DatePicker::make('tanggal')
                            ->required()
                            ->default(now())
                            ->format('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\Select::make('operasional')
                            ->label('Jenis Operasional')
                            ->options(Operasional::JENIS_OPERASIONAL)
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('atas_nama')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->currencyMask(
                                thousandSeparator: '.',
                                decimalSeparator: ',',
                                precision: 0,
                            )
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->maxLength(65535)
                            ->columnSpan(2),

                        Forms\Components\FileUpload::make('file_bukti')
                            ->label('Upload Bukti')
                            ->directory('bukti-operasional')
                            ->image() // Hanya menerima gambar
                            ->imagePreviewHeight('250')
                            ->maxSize(2048)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
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
                        'bahan_bakar' => 'danger',
                        'transportasi' => 'warning',
                        'perawatan' => 'info',
                        'gaji' => 'success',
                        'pinjaman' => 'gray',
                        'isi_saldo' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('atas_nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->color(
                        fn(string $state, Operasional $record): string =>
                        $record->operasional === 'isi_saldo' ? 'success' : 'danger'
                    )
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),

                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('file_bukti')
                    ->label('Bukti')
                    ->view('filament.tables.columns.preview-file')
                    ->alignCenter()
                    ->disableClick(), // Ini penting untuk mencegah redirect ke halaman edit

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
