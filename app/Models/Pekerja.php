<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pekerja extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pekerjas'; // Perbaikan nama tabel

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'pendapatan',
        'hutang',
    ];

    protected $dates = [
        'deleted_at',
    ];

    protected $casts = [
        'pendapatan' => 'decimal:0',
        'hutang' => 'decimal:0',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function transaksiDos()
    {
        return $this->belongsToMany(TransaksiDo::class, 'pekerja_transaksi_do')
            ->withPivot('pendapatan_pekerja')
            ->withTimestamps()
            ->orderBy('tanggal', 'desc');
    }

    // Helper method untuk mendapatkan total pendapatan dari transaksi
    public function getTotalPendapatanAttribute()
    {
        return $this->transaksiDos()
            ->sum('pekerja_transaksi_do.pendapatan_pekerja');
    }

    // Scope untuk data aktif
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
