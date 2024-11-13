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

    protected static function boot()
    {
        parent::boot();

        // Ketika transaksi DO dibuat
        static::created(function ($transaksiDo) {
            DB::transaction(function () use ($transaksiDo) {
                $saldoAwal = Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->value('saldo');

                // 1. Catat pemasukan dari pembayaran hutang
                if ($transaksiDo->bayar_hutang > 0) {
                    $saldoAwal += $transaksiDo->bayar_hutang;
                    LaporanKeuangan::create([
                        'tanggal' => $transaksiDo->tanggal,
                        'jenis' => 'masuk',
                        'tipe_transaksi' => 'transaksi_do',
                        'kategori_do' => 'bayar_hutang',
                        'keterangan' => "Pembayaran Hutang DO #{$transaksiDo->nomor}",
                        'nominal' => $transaksiDo->bayar_hutang,
                        'saldo_sebelum' => $saldoAwal - $transaksiDo->bayar_hutang,
                        'saldo_sesudah' => $saldoAwal,
                        'transaksi_do_id' => $transaksiDo->id
                    ]);
                }

                // 2. Catat pengeluaran biaya lain
                if ($transaksiDo->biaya_lain > 0) {
                    $saldoAwal -= $transaksiDo->biaya_lain;
                    LaporanKeuangan::create([
                        'tanggal' => $transaksiDo->tanggal,
                        'jenis' => 'keluar',
                        'tipe_transaksi' => 'transaksi_do',
                        'kategori_do' => 'biaya_lain',
                        'keterangan' => "Biaya Lain DO #{$transaksiDo->nomor}",
                        'nominal' => $transaksiDo->biaya_lain,
                        'saldo_sebelum' => $saldoAwal + $transaksiDo->biaya_lain,
                        'saldo_sesudah' => $saldoAwal,
                        'transaksi_do_id' => $transaksiDo->id
                    ]);
                }

                // 3. Catat pengeluaran upah bongkar
                if ($transaksiDo->upah_bongkar > 0) {
                    $saldoAwal -= $transaksiDo->upah_bongkar;
                    LaporanKeuangan::create([
                        'tanggal' => $transaksiDo->tanggal,
                        'jenis' => 'keluar',
                        'tipe_transaksi' => 'transaksi_do',
                        'kategori_do' => 'upah_bongkar',
                        'keterangan' => "Upah Bongkar DO #{$transaksiDo->nomor}",
                        'nominal' => $transaksiDo->upah_bongkar,
                        'saldo_sebelum' => $saldoAwal + $transaksiDo->upah_bongkar,
                        'saldo_sesudah' => $saldoAwal,
                        'transaksi_do_id' => $transaksiDo->id
                    ]);
                }

                // 4. Catat pengeluaran sisa bayar
                if ($transaksiDo->sisa_bayar > 0) {
                    $saldoAwal -= $transaksiDo->sisa_bayar;
                    LaporanKeuangan::create([
                        'tanggal' => $transaksiDo->tanggal,
                        'jenis' => 'keluar',
                        'tipe_transaksi' => 'transaksi_do',
                        'kategori_do' => 'pembayaran_do',
                        'keterangan' => "Pembayaran DO #{$transaksiDo->nomor}",
                        'nominal' => $transaksiDo->sisa_bayar,
                        'saldo_sebelum' => $saldoAwal + $transaksiDo->sisa_bayar,
                        'saldo_sesudah' => $saldoAwal,
                        'transaksi_do_id' => $transaksiDo->id
                    ]);
                }

                // Update saldo akhir perusahaan
                Perusahaan::where('id', auth()->user()->perusahaan_id)
                    ->update(['saldo' => $saldoAwal]);
            });
        });

        // Ketika transaksi DO dihapus (baik soft delete maupun force delete)
        static::deleting(function ($transaksiDo) {
            // Hapus semua record terkait di laporan keuangan
            // Karena menggunakan onDelete('cascade'), ini akan terhapus otomatis

            // Kembalikan saldo perusahaan
            $totalPengeluaran = $transaksiDo->biaya_lain + $transaksiDo->upah_bongkar + $transaksiDo->sisa_bayar;
            $totalPemasukan = $transaksiDo->bayar_hutang;
            $selisih = $totalPemasukan - $totalPengeluaran;

            Perusahaan::where('id', auth()->user()->perusahaan_id)
                ->decrement('saldo', $selisih);
        });
    }
}