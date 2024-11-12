<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\GenerateMonthlyNumber;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Support\Facades\{DB, Log};

class TransaksiDo extends Model
{
    use HasFactory, SoftDeletes, GenerateMonthlyNumber;

    protected $table = 'transaksi_do';

    protected $fillable = [
        'nomor',
        'tanggal',
        'penjual_id',
        'nomor_polisi',
        'tonase',
        'harga_satuan',
        'total',
        'upah_bongkar',
        'biaya_lain',
        'keterangan_biaya_lain',
        'hutang_awal',          // Updated
        'pembayaran_hutang',    // Updated
        'sisa_hutang_penjual',  // Updated
        'sisa_bayar',
        'file_do',
        'cara_bayar',
        'status_bayar',
        'catatan',
    ];

    // MASIH DIPAKAI - Tidak berubah
    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // MASIH DIPAKAI - Update casting untuk kolom baru
    protected $casts = [
        'tanggal' => 'datetime',
        'tonase' => 'integer',
        'harga_satuan' => 'integer',
        'total' => 'integer',
        'upah_bongkar' => 'integer',
        'biaya_lain' => 'integer',
        'hutang_awal' => 'integer',         // Updated
        'pembayaran_hutang' => 'integer',   // Updated
        'sisa_hutang_penjual' => 'integer', // Updated
        'sisa_bayar' => 'integer',
        'status_bayar' => 'string',
    ];

    protected $attributes = [
        'total' => 0,
        'upah_bongkar' => 0,
        'biaya_lain' => 0,
        'hutang_awal' => 0,           // Updated
        'pembayaran_hutang' => 0,     // Updated
        'sisa_hutang_penjual' => 0,   // Updated
        'sisa_bayar' => 0,
        'status_bayar' => 'Belum Lunas',
    ];

    // Relations
    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function laporanKeuangan(): HasMany
    {
        return $this->hasMany(LaporanKeuangan::class);
    }


    // Accessor untuk hutang penjual
    public function getHutangPenjualAttribute(): int
    {
        return $this->penjual ? $this->penjual->hutang : 0;
    }

    // Methods untuk perhitungan
    private function hitungTotal(): int
    {
        return $this->tonase * $this->harga_satuan;
    }

    public function riwayatHutang(): HasMany  // DITAMBAH - Relation baru
    {
        return $this->hasMany(RiwayatHutang::class);
    }

    private function hitungSisaBayar(): int
    {
        return max(0, $this->total - $this->upah_bongkar - $this->biaya_lain - $this->pembayaran_hutang);
    }

    private function hitungSisaHutang(): int
    {
        return max(0, $this->hutang_awal - $this->pembayaran_hutang);
    }
}