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
        'sisa_hutang', // tambahkan ini
        'sisa_bayar',
        'file_do',
        'cara_bayar',
        'catatan',
        // 'created_by',
        // 'updated_by',
    ];

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'

    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'tonase' => 'integer',
        'harga_satuan' => 'integer',
        'total' => 'integer',
        'upah_bongkar' => 'integer',
        'hutang' => 'integer',
        'bayar_hutang' => 'integer',
        'sisa_hutang' => 'integer',
        'sisa_bayar' => 'integer',
    ];

    protected $attributes = [
        'sisa_hutang' => 0,
    ];


    public function penjual()
    {
        return $this->belongsTo(Penjual::class, 'penjual_id');
    }

    public function pekerja()
    {
        return $this->belongsTo(Pekerja::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Accessor untuk perhitungan otomatis:

    // public function getTotalAttribute($value)
    // {
    //     return $this->tonase * $this->harga_satuan;
    // }

    public function getTotalAttribute($value)
    {
        // Kalkulasi total dan format dengan number_format
        $total = (int)$this->tonase * (int)$this->harga_satuan;
        return $total;
    }

    // public function getFormattedTotalAttribute()
    // {
    //     return number_format($this->total, 0, '', '.');
    // }

    // public function getFormattedHutangAttribute()
    // {
    //     return number_format($this->hutang, 0, '', '.');
    // }

    // public function getFormattedSisaHutangAttribute()
    // {
    //     return number_format($this->sisa_hutang, 0, '', '.');
    // }

    // public function getFormattedSisaBayarAttribute()
    // {
    //     return number_format($this->sisa_bayar, 0, '', '.');
    // }
}
