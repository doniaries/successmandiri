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
    protected $with = ['penjual']; // Default eager loading

    // // Default eager loading
    // protected $with = [
    //     'penjual:id,nama,hutang,alamat,telepon',
    //     'riwayatHutang'
    // ];



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
        'tonase' => 'decimal:2',
        'harga_satuan' => 'decimal:0',
        'total' => 'decimal:0',
        'upah_bongkar' => 'integer',
        'biaya_lain' => 'integer',
        'hutang_awal' => 'decimal:0',         // Updated
        'pembayaran_hutang' => 'decimal:0',   // Updated
        'sisa_hutang_penjual' => 'decimal:0', // Updated
        'sisa_bayar' => 'decimal:0',
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

    // Relationships
    // Relasi dengan lazy eager loading
    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function laporanKeuangan()
    {
        return $this->hasMany(LaporanKeuangan::class);
    }

    public function operasional()
    {
        return $this->hasMany(Operasional::class, 'transaksi_do_id');
    }

    // Accessor untuk hutang penjual
    public function getHutangPenjualAttribute(): int
    {
        return $this->penjual ? $this->penjual->hutang : 0;
    }

    // Methods untuk perhitungan
    public function hitungTotal(): int  // Dari private menjadi public
    {
        return $this->tonase * $this->harga_satuan;
    }

    // public function riwayatHutang(): HasMany
    // {
    //     return $this->hasMany(RiwayatHutang::class)
    //         ->latest()
    //         ->take(5);
    // }


    public function hitungSisaBayar(): int  // Dari private menjadi public
    {
        return max(0, $this->total - $this->upah_bongkar - $this->biaya_lain - $this->pembayaran_hutang);
    }

    public function hitungSisaHutang(): int  // Dari private menjadi public
    {
        return max(0, $this->hutang_awal - $this->pembayaran_hutang);
    }

    // Scopes
    // Scope untuk query tambahan jika diperlukan
    public function scopeWithCompleteData($query)
    {
        return $query->with([
            'laporanKeuangan' => fn($q) => $q->latest()->take(5)
        ]);
    }

    public function scopeRecentTransactions($query, $days = 30)
    {
        return $query->where('tanggal', '>=', now()->subDays($days));
    }

    // Optional: Tambahkan scope untuk eager loading
    public function scopeWithOperasional($query)
    {
        return $query->with(['operasional' => function ($q) {
            $q->select([
                'id',
                'transaksi_do_id',
                'tanggal',
                'operasional',
                'nominal',
                'kategori_id'
            ]);
        }]);
    }
}
