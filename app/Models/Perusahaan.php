<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'pimpinan',
        'kasir_id',
    ];
}