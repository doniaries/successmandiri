<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perusahaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'perusahaans';

    protected $fillable = [
        'nama',
        'logo_path',
        'favicon_path',
        'tema_warna',
        'alamat',
        'email',
        'telepon',
        'pimpinan',
        'is_active',
        'saldo',
        'npwp',
        'no_izin_usaha',
        'setting',
        'kasir_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'saldo' => 'decimal:0',
        'setting' => 'json',
    ];

    // Relasi dengan User (Kasir)
    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    // Helper method untuk format saldo
    public function getFormattedSaldoAttribute()
    {
        return 'Rp ' . number_format($this->saldo, 0, ',', '.');
    }
}
