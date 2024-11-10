<?php
// app/Models/RiwayatHutang.php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class RiwayatHutang extends Model
{
    use SoftDeletes;
    protected $table = 'riwayat_hutang';

    protected $fillable = [
        'tipe_entitas',
        'entitas_id',
        'nominal',
        'jenis',
        'keterangan',
        'operasional_id'
    ];

    public function operasional()
    {
        return $this->belongsTo(Operasional::class);
    }

    public function entitas()
    {
        return match ($this->tipe_entitas) {
            'penjual' => $this->belongsTo(Penjual::class, 'entitas_id'),
            default => null
        };
    }
}
