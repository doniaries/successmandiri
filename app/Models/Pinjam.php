<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pinjam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tanggal_pinjaman',
        'kategori_peminjam',
        'peminjam_id',
        'nominal',
        'deskripsi',
    ];

    public function pekerja(): BelongsTo
    {
        return $this->belongsTo(Pekerja::class, 'peminjam_id');
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class, 'peminjam_id');
    }

    // Helper method untuk mendapatkan nama peminjam
    public function getNamaPeminjamAttribute()
    {
        return match ($this->kategori_peminjam) {
            'Pekerja' => $this->pekerja?->nama,
            'Penjual' => $this->penjual?->nama,
            default => null
        };
    }
}