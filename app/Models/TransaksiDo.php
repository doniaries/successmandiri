<?php

namespace App\Models;

use App\Models\Team;
use App\Traits\DokumentasiTrait;
use App\Traits\LaporanKeuanganTrait;
use App\Traits\GenerateMonthlyNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};

class TransaksiDo extends Model
{
    use HasFactory, SoftDeletes, LaporanKeuanganTrait, DokumentasiTrait, GenerateMonthlyNumber;

    protected $table = 'transaksi_do';
    protected $with = ['penjual']; // Default eager loading


    protected $fillable = [
        'nomor',
        'team_id',
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

    const CARA_BAYAR = [
        'Tunai' => 'Tunai',           // Mempengaruhi saldo kas
        'Transfer' => 'Transfer',      // Tidak mempengaruhi saldo kas
        'Cair di Luar' => 'Cair di Luar'  // Tidak mempengaruhi saldo kas
    ];

    // Panggil method dari trait
    public function handlePembayaran()
    {
        $this->validateSaldoTunai($this->sisa_bayar);
        $this->handlePembayaranHutang($this->pembayaran_hutang);
        $this->updateSaldoPerusahaan();
    }


    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    // Tambahkan relation ke laporan keuangan
    public function laporanKeuangan()
    {
        return $this->hasMany(LaporanKeuangan::class, 'referensi_id')
            ->where('sumber_transaksi', 'DO');
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
}
