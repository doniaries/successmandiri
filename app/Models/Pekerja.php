<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerja extends Model
{
    protected $table = 'pekerja';

    protected $fillable = [
        'id',
        'nama',
        'alamat',
        'telepon',
        'pendapatan',
        'hutang',
    ];

    protected $casts = [
        'pendapatan' => 'decimal:0',
        'hutang' => 'decimal:0',
    ];


    public function operasional()
    {
        return $this->hasMany(Operasional::class);
    }
}
