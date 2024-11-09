<?php
// app/Models/KategoriOperasional.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriOperasional extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_operasional';

    protected $fillable = [
        'nama',
        'jenis',
        'keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];


    const JENIS_KATEGORI = [
        'pengeluaran' => 'Pengeluaran',
        'pemasukan' => 'Pemasukan'
    ];
    public function operasional()
    {
        return $this->hasMany(Operasional::class, 'kategori_id');
    }
}
