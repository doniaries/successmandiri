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

    // Relasi ke Pekerja
    public function pekerja(): BelongsTo
    {
        return $this->belongsTo(Pekerja::class, 'peminjam_id')
            ->where('kategori_peminjam', 'Pekerja');
    }

    // Relasi ke Penjual
    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class, 'peminjam_id')
            ->where('kategori_peminjam', 'Penjual');
    }

    // Method untuk mendapatkan data peminjam
    public function getPeminjam()
    {
        return match ($this->kategori_peminjam) {
            'Pekerja' => $this->pekerja,
            'Penjual' => $this->penjual,
            default => null,
        };
    }
}