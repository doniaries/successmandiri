<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Keuangan extends Model
{

    use HasFactory;


    protected $table = 'keuangans';

    protected $fillable = [
        'tanggal',
        'keterangan',
        'jenis_transaksi',
        'jumlah',
        'kategori',
        'sumber',
    ];
    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'decimal:0',
    ];

    protected $dates = [
        'tanggal',
    ];

    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function pekerja(): BelongsTo
    {
        return $this->belongsTo(Pekerja::class, 'pekerja_id');
    }

    public function isFromTransaksi(): bool
    {
        return $this->is_from_transaksi;
    }

    public function scopeManualEntry($query)
    {
        return $query->where('is_from_transaksi', false);
    }

    public function scopeFromTransaksi($query)
    {
        return $query->where('is_from_transaksi', true);
    }
}