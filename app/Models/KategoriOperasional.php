<?php
// app/Models/KategoriOperasional.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriOperasional extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_operasional';

    const BAYAR_HUTANG = 6;
    const TOTAL_DO = 8;
    const LAIN_LAIN = 7;
    const PINJAMAN = 1;
    const UANG_JALAN = 2;
    const BAHAN_BAKAR = 3;
    const PERAWATAN = 4;
    const GAJI = 5;

    const JENIS_KATEGORI = [
        'pengeluaran' => 'Pengeluaran',
        'pemasukan' => 'Pemasukan'
    ];

    protected $fillable = [
        'nama',
        'jenis',
        'keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];



    public function operasional()
    {
        return $this->hasMany(Operasional::class, 'kategori_id');
    }


    public function scopeFromDO($query)
    {
        return $query->where('is_from_transaksi', true);
    }

    public function scopePemasukan($query)
    {
        return $query->where('operasional', 'pemasukan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('operasional', 'pengeluaran');
    }

    public static function totalPemasukan($startDate = null, $endDate = null)
    {
        $query = static::pemasukan();
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }
        return $query->sum('nominal');
    }

    public static function totalPengeluaran($startDate = null, $endDate = null)
    {
        $query = static::pengeluaran();
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }
        return $query->sum('nominal');
    }
}
