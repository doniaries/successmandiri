<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pekerja extends Model
{
    use SoftDeletes;

    protected $table = 'pekerjas';

    protected $fillable = [
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

    public function transaksiDos()
    {
        return $this->belongsToMany(TransaksiDo::class, 'pekerja_transaksi_do')
            ->withPivot('pendapatan_pekerja')
            ->withTimestamps();
    }

    public function updateTotalPendapatan()
    {
        $total = $this->transaksiDos()
            ->whereNull('deleted_at')
            ->sum('pekerja_transaksi_do.pendapatan_pekerja');

        $this->update(['pendapatan' => $total]);
    }
}