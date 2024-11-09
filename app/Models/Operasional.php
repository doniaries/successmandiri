<?php

namespace App\Models;

use App\Models\User;
use App\Models\Pekerja;
use App\Models\Penjual;
use App\Models\KategoriOperasional;
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
        'operasional',
        'kategori_id',
        'tipe_nama',
        'penjual_id',
        'pekerja_id',
        'user_id',
        'nominal',
        'keterangan',
        'file_bukti',
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
    ];

    const JENIS_OPERASIONAL = [
        'pemasukan' => 'Pemasukan',
        'pengeluaran' => 'Pengeluaran',
    ];

    // const KATEGORI_OPERASIONAL = [
    //     'bayar_hutang' => 'Bayar Hutang',
    //     'uang_jalan' => 'Uang Jalan',
    //     'gaji' => 'Gaji',
    //     'bahan_bakar' => 'Bahan Bakar',
    //     'perawatan' => 'Perawatan',
    //     'lain_lain' => 'Lain-lain',
    //     'pinjaman' => 'Pinjaman'
    // ];

    // Relations
    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function pekerja()
    {
        return $this->belongsTo(Pekerja::class);
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
            'pekerja' => $this->pekerja?->nama,
            'user' => $this->user?->name,
            default => null
        };
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriOperasional::class, 'kategori_id');
    }
}
