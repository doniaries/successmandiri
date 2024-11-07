<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Keuangan</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 30px;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            padding: 0;
            font-size: 20px;
        }

        .periode {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .nominal {
            text-align: right;
        }

        .total {
            font-weight: bold;
        }

        .pemasukan {
            color: #10b981;
        }

        .pengeluaran {
            color: #ef4444;
        }

        .summary {
            margin-top: 30px;
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN</h1>
        <p>Periode: {{ $periode['dari'] }} - {{ $periode['sampai'] }}</p>
    </div>

    @foreach ($dailyTransactions as $date => $daily)
        <div class="daily-section {{ !$loop->last ? 'page-break' : '' }}">
            <h3>Tanggal: {{ $daily['tanggal'] }}</h3>

            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th>Pemasukan</th>
                        <th>Pengeluaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daily['transactions'] as $transaction)
                        <tr>
                            <td>{{ $transaction->tanggal->format('H:i') }}</td>
                            <td>{{ $transaction->sumber }}</td>
                            <td>{{ $transaction->keterangan }}</td>
                            <td class="nominal">
                                @if ($transaction->jenis === 'pemasukan')
                                    Rp {{ number_format($transaction->nominal, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="nominal">
                                @if ($transaction->jenis === 'pengeluaran')
                                    Rp {{ number_format($transaction->nominal, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="3" class="text-right">Total Harian:</td>
                        <td class="nominal pemasukan">Rp {{ number_format($daily['total_pemasukan'], 0, ',', '.') }}</td>
                        <td class="nominal pengeluaran">Rp
                            {{ number_format($daily['total_pengeluaran'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="summary">
        <h3>RINGKASAN PERIODE</h3>
        <p>Total Pemasukan: <span class="pemasukan">Rp {{ number_format($total['pemasukan'], 0, ',', '.') }}</span></p>
        <p>Total Pengeluaran: <span class="pengeluaran">Rp
                {{ number_format($total['pengeluaran'], 0, ',', '.') }}</span></p>
        <p>Saldo: <strong>Rp {{ number_format($total['pemasukan'] - $total['pengeluaran'], 0, ',', '.') }}</strong></p>
    </div>
</body>

</html>
