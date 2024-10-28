<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransaksiDo extends Model
{

    use HasFactory, SoftDeletes;
    protected $table = 'transaksi_do';

    protected $fillable = [
        'nomor',
        'tanggal',
        'penjual_id',
        'nomor_polisi',
        'tonase',
        'harga_satuan',
        'total',
        'upah_bongkar',
        'hutang',
        'bayar_hutang',
        'sisa_bayar',
        'file_do',
        'cara_bayar',
        'catatan',
        'deleted_at',
        // 'created_by',
        // 'updated_by',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function pekerja()
    {
        return $this->belongsTo(Pekerja::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    //Accessor untuk perhitungan otomatis:
    public function getTotalAttribute($value)
    {
        return $this->tonase * $this->harga_satuan;
    }
}