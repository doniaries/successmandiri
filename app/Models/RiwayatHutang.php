<?php
// app/Models/RiwayatHutang.php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Penjual::class, 'entitas_id')
            ->withTrashed();
    }

    public function transaksiDo(): BelongsTo
    {
        return $this->belongsTo(TransaksiDo::class, 'transaksi_do_id')
            ->withTrashed();
    }

    public function operasional(): BelongsTo
    {
        return $this->belongsTo(Operasional::class, 'operasional_id')
            ->withTrashed();
    }
}
