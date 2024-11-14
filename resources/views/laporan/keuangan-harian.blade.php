<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Keuangan Harian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .company-logo {
            max-width: 150px;
            height: auto;
        }

        .company-info {
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 11px;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .amount {
            text-align: right;
        }

        .summary {
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .total {
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
        }

        .green {
            color: #22c55e;
        }

        .red {
            color: #ef4444;
        }
    </style>
</head>

<body>
    <div class="header">
        @if ($perusahaan->logo_path)
            <img src="{{ asset('storage/' . $perusahaan->logo_path) }}" class="company-logo">
        @endif
        <div class="company-info">
            <div class="company-name">{{ $perusahaan->nama }}</div>
            <div>{{ $perusahaan->alamat }}</div>
            <div>Telp: {{ $perusahaan->telepon }}</div>
            @if ($perusahaan->email)
                <div>Email: {{ $perusahaan->email }}</div>
            @endif
            @if ($perusahaan->npwp)
                <div>NPWP: {{ $perusahaan->npwp }}</div>
            @endif
        </div>
    </div>

    <h2 style="text-align: center;">LAPORAN KEUANGAN HARIAN</h2>
    <h3 style="text-align: center;">Tanggal: {{ $tanggal }}</h3>

    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>No. Transaksi</th>
                <th>Sumber</th>
                <th>Nama</th>
                <th>Keterangan</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaksi as $t)
                <tr>
                    <td>{{ Carbon\Carbon::parse($t->tanggal)->format('H:i') }}</td>
                    <td>{{ $t->nomor_transaksi ?? '-' }}</td>
                    <td>{{ $t->tipe_transaksi == 'transaksi_do' ? 'Transaksi DO' : 'Operasional' }}</td>
                    <td>{{ $t->nama_penjual ?? '-' }}</td>
                    <td>{{ $t->keterangan }}</td>
                    <td class="amount">
                        @if ($t->jenis == 'masuk')
                            Rp {{ number_format($t->nominal, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="amount">
                        @if ($t->jenis == 'keluar')
                            Rp {{ number_format($t->nominal, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="amount">
                        Rp {{ number_format($t->saldo_sesudah, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td width="60%"><strong>RINGKASAN TRANSAKSI</strong></td>
                <td width="20%" class="amount"><strong>Total</strong></td>
                <td width="20%"></td>
            </tr>
            <tr>
                <td>Total Pemasukan</td>
                <td class="amount green">Rp {{ number_format($total['pemasukan'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Total Pengeluaran</td>
                <td class="amount red">Rp {{ number_format($total['pengeluaran'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr class="total">
                <td>Saldo Akhir</td>
                <td class="amount">Rp {{ number_format($total['saldo_akhir'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <p>Pimpinan</p>
        <br><br><br>
        <p>{{ $perusahaan->pimpinan }}</p>
    </div>
</body>

</html>
