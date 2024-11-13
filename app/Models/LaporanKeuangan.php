<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKeuangan extends Model
{
    protected $table = 'laporan_keuangan';

    const KATEGORI_DO = [
        '' => 'Bayar Hutang',
        'biaya_lain' => 'Biaya Lain',
        'upah_bongkar' => 'Upah Bongkar',
        'pembayaran_do' => 'Pembayaran DO',
    ];

    protected $fillable = [
        'tanggal',
        'jenis', // masuk/keluar
        'tipe_transaksi', // transaksi_do/operasional
        'kategori_do',
        'kategori_operasional_id',
        'keterangan',
        'nominal',
        'saldo_sebelum',
        'saldo_sesudah',
        'transaksi_do_id',
        'operasional_id',
        'created_by',
        'nomor_transaksi',
        'nama_penjual'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:0',
        'saldo_sebelum' => 'decimal:0',
        'saldo_sesudah' => 'decimal:0',
    ];

    // Relationships
    public function kategoriOperasional()
    {
        return $this->belongsTo(KategoriOperasional::class);
    }

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class, 'transaksi_do_id');
    }

    public function operasional()
    {
        return $this->belongsTo(Operasional::class, 'operasional_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePemasukan($query)
    {
        return $query->where('jenis', 'masuk');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('jenis', 'keluar');
    }
}