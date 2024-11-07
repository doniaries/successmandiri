<?php

namespace App\Filament\Resources\KeuanganResource\Widgets;

use App\Models\Keuangan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class KeuanganChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Keuangan Bulan Ini';
    protected static ?string $pollingInterval = '10s';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = collect(range(1, Carbon::now()->daysInMonth))->map(function ($day) {
            $date = Carbon::create(null, Carbon::now()->month, $day);

            $pemasukan = Keuangan::where('jenis', 'pemasukan')
                ->whereDate('tanggal', $date)
                ->sum('nominal');

            $pengeluaran = Keuangan::where('jenis', 'pengeluaran')
                ->whereDate('tanggal', $date)
                ->sum('nominal');

            return [
                'date' => $date->format('d/m'),
                'Pemasukan' => $pemasukan,
                'Pengeluaran' => $pengeluaran,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $days->pluck('Pemasukan'),
                    'borderColor' => '#10B981',
                    'backgroundColor' => '#10B981',
                    'fill' => false,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $days->pluck('Pengeluaran'),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => '#EF4444',
                    'fill' => false,
                ],
            ],
            'labels' => $days->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
