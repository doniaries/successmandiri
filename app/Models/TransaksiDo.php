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
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'

    ];

    protected $casts = [
        // 'tanggal' => 'datetime',
        // 'tonase' => 'decimal:0',
        'hutang' => 'decimal:0',
        'harga_satuan' => 'decimal:0',
        'total' => 'decimal:0',
        'upah_bongkar' => 'decimal:0',
        'hutang' => 'decimal:0',
        'bayar_hutang' => 'decimal:0',
        'sisa_bayar' => 'decimal:0',
        'hutang' => 'integer',
        'tanggal' => 'datetime',
        'tonase' => 'integer',          // Ubah ke integer
        'harga_satuan' => 'integer',    // Ubah ke integer
        'total' => 'integer',           // Ubah ke integer
        'upah_bongkar' => 'integer',    // Ubah ke integer
        'hutang' => 'integer',          // Ubah ke integer
        'bayar_hutang' => 'integer',    // Ubah ke integer
        'sisa_hutang' => 'integer',     // Ubah ke integer
        'sisa_bayar' => 'integer',      // Ubah ke integer
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
