<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keuangan extends Model
{
    use SoftDeletes;

    protected $table = 'keuangans';

    protected $fillable = [
        'tanggal',
        'jenis', // enum: pemasukan/pengeluaran
        'kategori', // enum: transaksi_do, bayar_hutang, lainnya
        'referensi_id', // ID dari tabel terkait (transaksi_do atau pinjam)
        'keterangan',
        'nominal',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:0',
    ];

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class, 'referensi_id');
    }

    public function pinjam()
    {
        return $this->belongsTo(Pinjam::class, 'referensi_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
