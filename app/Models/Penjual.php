<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penjual extends Model
{

    use HasFactory, SoftDeletes;
    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'saldo',
        'hutang',
    ];

    // Optional: tambahkan casting
    protected $casts = [
        'hutang' => 'decimal:0',
    ];
}
