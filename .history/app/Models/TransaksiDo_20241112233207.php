<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\GenerateMonthlyNumber;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Support\Facades\{DB, Log};
use Filament\Notifications\Notification;

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
        'hutang_awal',            // Updated
        'pembayaran_hutang',      // Updated
        'sisa_hutang_penjual',    // Updated
        'sisa_bayar',
        'file_do',
        'cara_bayar',
        'status_bayar',
        'catatan',
    ];

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'tonase' => 'decimal:2',
        'harga_satuan' => 'decimal:0',
        'total' => 'decimal:0',
        'upah_bongkar' => 'decimal:0',
        'biaya_lain' => 'decimal:0',
        'hutang_awal' => 'decimal:0',          // Updated
        'pembayaran_hutang' => 'decimal:0',    // Updated
        'sisa_hutang_penjual' => 'decimal:0',  // Updated
        'sisa_bayar' => 'decimal:0',
    ];



    // Add relation to riwayat hutang
    public function riwayatHutang()
    {
        return $this->hasMany(RiwayatHutang::class);
    }


    // Relations
    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function laporanKeuangan(): HasMany
    {
        return $this->hasMany(LaporanKeuangan::class);
    }

    public function operasional(): HasMany
    {
        return $this->hasMany(Operasional::class, 'penjual_id', 'penjual_id');
    }

    // Helper method untuk mengambil hutang penjual saat ini
    public function getHutangPenjualAttribute()
    {
        return $this->penjual?->hutang ?? 0;
    }

    // Helper method untuk validasi pembayaran hutang
    public function validatePembayaranHutang($nominal): bool
    {
        if (!$this->penjual) {
            return false;
        }

        // Saat create, cek terhadap hutang penjual
        if (!$this->exists) {
            return $nominal <= $this->penjual->hutang;
        }

        // Saat edit, cek terhadap hutang awal
        return $nominal <= $this->hutang_awal;
    }
}
