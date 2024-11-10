<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Keuangan extends Model
{

    use HasFactory, SoftDeletes;


    protected $table = 'keuangans';

    protected $fillable = [
        'tanggal',
        'keterangan',
        'jenis_transaksi',
        'jumlah',
        'kategori',
        'sumber',
    ];
    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'decimal:0',
    ];

    protected $dates = [
        'tanggal',
    ];
}
