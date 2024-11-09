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
        'keterangan',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function operasional()
    {
        return $this->hasMany(Operasional::class, 'kategori_id');
    }
}
