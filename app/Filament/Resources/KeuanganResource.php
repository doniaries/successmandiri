<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeuanganResource\Pages;
use App\Models\Keuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\IconPosition;
use Carbon\Carbon;

class KeuanganResource extends Resource
{
    protected static ?string $model = Keuangan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ])
                    ->sortable()
                    ->color(
                        fn($record): string =>
                        $record->jenis === 'pemasukan' ? 'success' : 'danger'
                    ),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->label('Jenis Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->where('jenis', $value)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['value']) {
                            return null;
                        }
                        return 'Jenis: ' . $data['value'];
                    }),

                Tables\Filters\Filter::make('periode')
                    ->form([
                        Forms\Components\Select::make('range')
                            ->label('Pilih Periode')
                            ->options([
                                'today' => 'Hari Ini',
                                'yesterday' => 'Kemarin',
                                'this_week' => 'Minggu Ini',
                                'last_week' => 'Minggu Lalu',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Lalu',
                                'custom' => 'Kustom',
                            ])
                            ->default('this_month')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'today') {
                                    $set('from', today());
                                    $set('until', today());
                                } elseif ($state === 'yesterday') {
                                    $set('from', today()->subDay());
                                    $set('until', today()->subDay());
                                } elseif ($state === 'this_week') {
                                    $set('from', today()->startOfWeek());
                                    $set('until', today()->endOfWeek());
                                } elseif ($state === 'last_week') {
                                    $set('from', today()->subWeek()->startOfWeek());
                                    $set('until', today()->subWeek()->endOfWeek());
                                } elseif ($state === 'this_month') {
                                    $set('from', today()->startOfMonth());
                                    $set('until', today()->endOfMonth());
                                } elseif ($state === 'last_month') {
                                    $set('from', today()->subMonth()->startOfMonth());
                                    $set('until', today()->subMonth()->endOfMonth());
                                }
                            }),
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(today()->startOfMonth())
                            ->visible(fn(Forms\Get $get) => $get('range') === 'custom'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(today()->endOfMonth())
                            ->visible(fn(Forms\Get $get) => $get('range') === 'custom'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('sumber')
                    ->label('Sumber')
                    ->options([
                        'transaksi_do' => 'Transaksi DO',
                        'operasional' => 'Operasional',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->where('sumber', $value)
                        );
                    })
            ])
            ->headerActions([
                Action::make('export_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->iconPosition(IconPosition::After)
                    ->color('success')
                    ->action(function ($action) {
                        $query = static::getModel()::query();
                        $filterData = $action->getLivewire()->tableFilters;
                        $activeTab = $action->getLivewire()->activeTab ?? 'semua';

                        // Inisialisasi nama file
                        $fileName = 'laporan-keuangan';
                        $from = now()->startOfMonth();
                        $until = now();

                        // Tambahkan tab ke nama file jika bukan 'semua'
                        if ($activeTab !== 'semua') {
                            $fileName .= '-' . $activeTab;
                            $query->where('jenis', $activeTab);
                        }

                        // Apply filters
                        if (!empty($filterData)) {
                            if (isset($filterData['periode'])) {
                                $data = $filterData['periode']['range'];
                                if ($data === 'custom') {
                                    $from = Carbon::parse($filterData['periode']['from']);
                                    $until = Carbon::parse($filterData['periode']['until']);
                                    $fileName .= '-' . $from->format('d-m-Y') . '-sd-' . $until->format('d-m-Y');
                                } else {
                                    switch ($data) {
                                        case 'today':
                                            $from = $until = today();
                                            $fileName .= '-' . $from->format('d-m-Y');
                                            break;
                                        case 'yesterday':
                                            $from = $until = today()->subDay();
                                            $fileName .= '-' . $from->format('d-m-Y');
                                            break;
                                        case 'this_week':
                                            $from = today()->startOfWeek();
                                            $until = today()->endOfWeek();
                                            $fileName .= '-minggu-ini';
                                            break;
                                        case 'last_week':
                                            $from = today()->subWeek()->startOfWeek();
                                            $until = today()->subWeek()->endOfWeek();
                                            $fileName .= '-minggu-lalu';
                                            break;
                                        case 'this_month':
                                            $from = today()->startOfMonth();
                                            $until = today()->endOfMonth();
                                            $fileName .= '-' . $from->format('m-Y');
                                            break;
                                        case 'last_month':
                                            $from = today()->subMonth()->startOfMonth();
                                            $until = today()->subMonth()->endOfMonth();
                                            $fileName .= '-' . $from->format('m-Y');
                                            break;
                                    }
                                }

                                $query->whereDate('tanggal', '>=', $from)
                                    ->whereDate('tanggal', '<=', $until);
                            }

                            if (isset($filterData['sumber'])) {
                                $query->whereIn('sumber', $filterData['sumber']);
                                if (count($filterData['sumber']) === 1) {
                                    $fileName .= '-' . str_replace('_', '-', $filterData['sumber'][0]);
                                }
                            }
                        }

                        $dailyTransactions = $query->get()
                            ->groupBy(function ($item) {
                                return $item->tanggal->format('Y-m-d');
                            })
                            ->map(function ($group) {
                                return [
                                    'tanggal' => $group->first()->tanggal->format('d/m/Y'),
                                    'total_pemasukan' => $group->where('jenis', 'pemasukan')->sum('nominal'),
                                    'total_pengeluaran' => $group->where('jenis', 'pengeluaran')->sum('nominal'),
                                    'transactions' => $group
                                ];
                            });

                        $pdf = Pdf::loadView('pdf.laporan-keuangan', [
                            'dailyTransactions' => $dailyTransactions,
                            'periode' => [
                                'dari' => $from->format('d/m/Y'),
                                'sampai' => $until->format('d/m/Y'),
                            ],
                            'total' => [
                                'pemasukan' => $dailyTransactions->sum('total_pemasukan'),
                                'pengeluaran' => $dailyTransactions->sum('total_pengeluaran'),
                            ]
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $fileName . '.pdf');
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKeuangans::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
