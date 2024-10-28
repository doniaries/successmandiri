<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjual extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'saldo',
        'hutang',
    ];
}