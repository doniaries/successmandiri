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
        'nomor'
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
        'created_by',
        'updated_by',
    ];
}