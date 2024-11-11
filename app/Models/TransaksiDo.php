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
        'hutang',
        'bayar_hutang',
        'sisa_hutang',
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
        'tonase' => 'integer',
        'harga_satuan' => 'integer',
        'total' => 'integer',
        'upah_bongkar' => 'integer',
        'biaya_lain' => 'integer',
        'hutang' => 'integer',
        'bayar_hutang' => 'integer',
        'sisa_hutang' => 'integer',
        'sisa_bayar' => 'integer',
        'status_bayar' => 'string',
    ];

    protected $attributes = [
        'total' => 0,
        'upah_bongkar' => 0,
        'biaya_lain' => 0,
        'hutang' => 0,
        'bayar_hutang' => 0,
        'sisa_hutang' => 0,
        'sisa_bayar' => 0,
        'status_bayar' => 'Belum Lunas',
    ];

    // Relations
    public function penjual(): BelongsTo
    {
        return $this->belongsTo(Penjual::class);
    }

    public function operasional(): HasMany
    {
        return $this->hasMany(Operasional::class, 'penjual_id', 'penjual_id');
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

    private function hitungSisaBayar(): int
    {
        return max(0, $this->total - $this->upah_bongkar - $this->biaya_lain - $this->bayar_hutang);
    }

    private function hitungSisaHutang(): int
    {
        return max(0, $this->hutang - $this->bayar_hutang);
    }

    // Method untuk proses transaksi keuangan
    private function prosesTransaksiKeuangan(): void
    {
        try {
            DB::beginTransaction();

            $perusahaan = Perusahaan::firstOrFail();

            // 1. Proses pembayaran hutang
            if ($this->bayar_hutang > 0) {
                if (!$this->penjual) {
                    throw new \Exception('Data penjual tidak ditemukan');
                }

                $this->penjual->decrement('hutang', $this->bayar_hutang);

                Keuangan::create([
                    'tanggal' => $this->tanggal,
                    'jenis_transaksi' => 'Masuk',
                    'kategori' => 'Bayar Hutang',
                    'sumber' => 'Transaksi DO',
                    'jumlah' => $this->bayar_hutang,
                    'keterangan' => "Pembayaran hutang penjual {$this->penjual->nama} via DO #{$this->nomor}"
                ]);
            }

            // 2. Proses pemasukan biaya
            $totalBiaya = $this->upah_bongkar + $this->biaya_lain;
            if ($totalBiaya > 0) {
                $perusahaan->increment('saldo', $totalBiaya);

                Keuangan::create([
                    'tanggal' => $this->tanggal,
                    'jenis_transaksi' => 'Masuk',
                    'kategori' => 'Biaya DO',
                    'sumber' => 'Transaksi DO',
                    'jumlah' => $totalBiaya,
                    'keterangan' => "Pemasukan biaya DO #{$this->nomor}"
                ]);
            }

            // 3. Proses pembayaran DO
            if ($this->sisa_bayar > 0) {
                if ($perusahaan->saldo < $this->sisa_bayar) {
                    throw new \Exception('Saldo perusahaan tidak mencukupi');
                }

                $perusahaan->decrement('saldo', $this->sisa_bayar);

                Keuangan::create([
                    'tanggal' => $this->tanggal,
                    'jenis_transaksi' => 'Keluar',
                    'kategori' => 'Pembayaran DO',
                    'sumber' => 'Transaksi DO',
                    'jumlah' => $this->sisa_bayar,
                    'keterangan' => "Pembayaran DO #{$this->nomor} ke {$this->penjual->nama}"
                ]);
            }

            DB::commit();

            Log::info('Transaksi DO Berhasil:', [
                'nomor' => $this->nomor,
                'total' => $this->total,
                'bayar_hutang' => $this->bayar_hutang,
                'sisa_bayar' => $this->sisa_bayar
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error Transaksi DO:', [
                'nomor' => $this->nomor,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set nilai awal dari penjual
            $model->hutang = $model->hutang_penjual;

            // Hitung nilai turunan
            $model->total = $model->hitungTotal();
            $model->sisa_hutang = $model->hitungSisaHutang();
            $model->sisa_bayar = $model->hitungSisaBayar();
        });
    }
}