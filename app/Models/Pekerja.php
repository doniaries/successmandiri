<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerja extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'saldo',
        'hutang',
    ];
}