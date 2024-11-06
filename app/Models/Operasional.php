<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Operasional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operasional';

    protected $fillable = [
        'tanggal',
        'operasional',
        'atas_nama',
        'nominal',
        'keterangan',
        'file_bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:0',
    ];

    const JENIS_OPERASIONAL = [
        'bahan_bakar' => 'Bahan Bakar',
        'transportasi' => 'Transportasi',
        'perawatan' => 'Perawatan',
        'gaji' => 'Gaji',
        'pinjaman' => 'Pinjaman',
        'isi_saldo' => 'Isi Saldo',
    ];
}
