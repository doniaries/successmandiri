<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keuangan extends Model
{
    use SoftDeletes;

    protected $table = 'keuangans';

    protected $fillable = [
        'tanggal',
        'jenis', // enum: pemasukan/pengeluaran
        'kategori', // enum: transaksi_do, bayar_hutang, transfer_masuk, lainnya
        'referensi_id',
        'keterangan',
        'nominal',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:0',
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class, 'referensi_id')
            ->where('kategori', 'transaksi_do');
    }

    // Scopes untuk reporting
    public function scopePemasukan($query)
    {
        return $query->where('jenis', 'pemasukan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('jenis', 'pengeluaran');
    }

    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopePeriode($query, $start, $end)
    {
        return $query->whereBetween('tanggal', [$start, $end]);
    }

    // Method untuk menghitung saldo
    public static function getSaldo()
    {
        $pemasukan = static::pemasukan()->sum('nominal');
        $pengeluaran = static::pengeluaran()->sum('nominal');
        return $pemasukan - $pengeluaran;
    }

    public static function getSaldoAtDate($date)
    {
        $pemasukan = static::pemasukan()
            ->where('tanggal', '<=', $date)
            ->sum('nominal');

        $pengeluaran = static::pengeluaran()
            ->where('tanggal', '<=', $date)
            ->sum('nominal');

        return $pemasukan - $pengeluaran;
    }

    // Method untuk mencatat transaksi DO
    public static function catatTransaksiDO(TransaksiDo $transaksiDo)
    {
        // 1. Jika transfer, catat pemasukan ke saldo
        if ($transaksiDo->cara_bayar === 'Transfer') {
            static::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pemasukan',
                'kategori' => 'transfer_masuk',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->sisa_bayar,
                'keterangan' => "Transfer masuk DO #{$transaksiDo->nomor}",
                'created_by' => auth()->id(),
            ]);
        }

        // 2. Catat pengeluaran DO
        static::create([
            'tanggal' => $transaksiDo->tanggal,
            'jenis' => 'pengeluaran',
            'kategori' => 'transaksi_do',
            'referensi_id' => $transaksiDo->id,
            'nominal' => $transaksiDo->sisa_bayar,
            'keterangan' => "Pembayaran DO #{$transaksiDo->nomor} via {$transaksiDo->cara_bayar}",
            'created_by' => auth()->id(),
        ]);

        // 3. Jika ada pembayaran hutang, catat sebagai pemasukan
        if ($transaksiDo->bayar_hutang > 0) {
            static::create([
                'tanggal' => $transaksiDo->tanggal,
                'jenis' => 'pemasukan',
                'kategori' => 'bayar_hutang',
                'referensi_id' => $transaksiDo->id,
                'nominal' => $transaksiDo->bayar_hutang,
                'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                'created_by' => auth()->id(),
            ]);
        }
    }
}
