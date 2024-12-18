<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;
use App\Models\Penjual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Operasional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operasional';

    protected $fillable = [
        'tanggal',
        'team_id',
        'operasional',
        'kategori', // ganti dari kategori_id
        'tipe_nama',
        'penjual_id',
        'user_id',
        'nominal',
        'keterangan',
        'file_bukti',
        'is_from_transaksi',
    ];

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:0',
        'is_from_transaksi' => 'boolean',
    ];

    const JENIS_OPERASIONAL = [
        'pemasukan' => 'Pemasukan',
        'pengeluaran' => 'Pengeluaran',
    ];

    const KATEGORI_OPERASIONAL = [
        'bayar_hutang' => 'Bayar Hutang',
        'uang_jalan' => 'Uang Jalan',
        'gaji' => 'Gaji',
        'bahan_bakar' => 'Bahan Bakar',
        'perawatan' => 'Perawatan',
        'pinjaman' => 'Pinjaman',
        'lain_lain' => 'Lain-lain',
    ];

    // Relations
    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function pekerja(): BelongsTo
    {
        return $this->belongsTo(Pekerja::class, 'pekerja_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor untuk mendapatkan nama
    public function getNamaAttribute()
    {
        return match ($this->tipe_nama) {
            'penjual' => $this->penjual?->nama,
            'user' => $this->user?->name,
            default => null
        };
    }

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class, 'transaksi_do_id');
    }


    // Helper method untuk cek apakah data dari transaksi
    public function isFromTransaksi(): bool
    {
        return $this->is_from_transaksi;
    }

    // Scope untuk query
    public function scopeManualEntry($query)
    {
        return $query->where('is_from_transaksi', false);
    }

    public function scopeFromTransaksi($query)
    {
        return $query->where('is_from_transaksi', true);
    }

    public function getKategoriNameAttribute(): string
    {
        return static::KATEGORI_OPERASIONAL[$this->kategori] ?? '-';
    }
}
