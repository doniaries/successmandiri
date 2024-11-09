<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjual extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'hutang',
    ];

    protected $casts = [
        'hutang' => 'decimal:0',
    ];

    public function riwayatHutang()
    {
        return $this->hasMany(RiwayatHutang::class, 'entitas_id')
            ->where('tipe_entitas', 'penjual');
    }
}
