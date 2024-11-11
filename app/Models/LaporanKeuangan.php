<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKeuangan extends Model
{
    protected $table = 'laporan_keuangan';

    const KATEGORI_DO = [
        'bayar_hutang' => 'Bayar Hutang',
        'biaya_lain' => 'Biaya Lain',
        'upah_bongkar' => 'Upah Bongkar',
        'pembayaran_do' => 'Pembayaran DO',
    ];

    protected $fillable = [
        'tanggal',
        'jenis',
        'tipe_transaksi',
        'kategori_do',
        'kategori_operasional_id',
        'keterangan',
        'nominal',
        'saldo_sebelum',
        'saldo_sesudah',
        'transaksi_do_id',
        'operasional_id',
        'created_by'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:0',
        'saldo_sebelum' => 'decimal:0',
        'saldo_sesudah' => 'decimal:0',
    ];

    public function kategoriOperasional()
    {
        return $this->belongsTo(KategoriOperasional::class);
    }

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class);
    }

    public function operasional()
    {
        return $this->belongsTo(Operasional::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
