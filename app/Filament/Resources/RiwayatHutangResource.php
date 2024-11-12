<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiwayatHutangResource\Pages;
use App\Models\RiwayatHutang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RiwayatHutangResource extends Resource
{
    protected static ?string $model = RiwayatHutang::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock-rewind';
    protected static ?string $navigationLabel = 'Riwayat Hutang';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('tipe_entitas')
                            ->label('Tipe')
                            ->options([
                                'penjual' => 'Penjual'
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('entitas_id')
                            ->label('Nama')
                            ->relationship('entitas', 'nama')
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\Select::make('jenis')
                            ->label('Jenis')
                            ->options([
                                'penambahan' => 'Penambahan',
                                'pengurangan' => 'Pengurangan'
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('hutang_sebelum')
                            ->label('Hutang Sebelum')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\TextInput::make('hutang_sesudah')
                            ->label('Hutang Sesudah')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('entitas.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'penambahan' => 'danger',
                        'pengurangan' => 'success',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),

                Tables\Columns\TextColumn::make('hutang_sebelum')
                    ->label('Hutang Sebelum')
                    ->money('IDR')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('hutang_sesudah')
                    ->label('Hutang Sesudah')
                    ->money('IDR')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaksiDo.nomor')
                    ->label('No. DO')
                    ->searchable()
                    ->sortable()
                    ->url(fn(Model $record) => $record->transaksi_do_id ?
                        TransaksiDoResource::getUrl('edit', ['record' => $record->transaksi_do_id]) : null),

                Tables\Columns\TextColumn::make('operasional.nomor')
                    ->label('No. Operasional')
                    ->searchable()
                    ->sortable()
                    ->url(fn(Model $record) => $record->operasional_id ?
                        OperasionalResource::getUrl('edit', ['record' => $record->operasional_id]) : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe_entitas')
                    ->label('Tipe')
                    ->options([
                        'penjual' => 'Penjual'
                    ]),

                Tables\Filters\SelectFilter::make('entitas')
                    ->label('Nama')
                    ->relationship('entitas', 'nama')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'penambahan' => 'Penambahan',
                        'pengurangan' => 'Pengurangan'
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayatHutang::route('/'),
            'view' => Pages\ViewRiwayatHutang::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
