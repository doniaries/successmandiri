<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hugomyb\FilamentMediaAction\Models\Media;
use App\Traits\GenerateMonthlyNumber;

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
    ];

    protected $attributes = [
        'total' => 0,
        'upah_bongkar' => 0,
        'biaya_lain' => 0,
        'hutang' => 0,
        'bayar_hutang' => 0,
        'sisa_hutang' => 0,
        'sisa_bayar' => 0,
        'status_bayar' => 'Belum Bayar',
        'lunas' => 'lunas',
    ];


    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function pekerjas()
    {
        return $this->belongsToMany(Pekerja::class, 'pekerja_transaksi_do')
            ->withPivot('pendapatan_pekerja')
            ->withTimestamps();
    }

    public function getTotalPekerjaAttribute()
    {
        return $this->pekerjas()
            ->whereNull('pekerjas.deleted_at')
            ->count();
    }

    public function updatePendapatanPekerja()
    {
        $jumlahPekerja = $this->pekerjas()->count();

        if ($jumlahPekerja > 0) {
            $pendapatanPerPekerja = $this->upah_bongkar / $jumlahPekerja;

            foreach ($this->pekerjas as $pekerja) {
                // Reset pendapatan lama jika ada
                $pendapatanLama = $pekerja->pivot->pendapatan_pekerja ?? 0;
                $pekerja->decrement('pendapatan', $pendapatanLama);

                // Update pendapatan baru
                $this->pekerjas()->updateExistingPivot($pekerja->id, [
                    'pendapatan_pekerja' => $pendapatanPerPekerja
                ]);
                $pekerja->increment('pendapatan', $pendapatanPerPekerja);
            }
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Saat transaksi dibuat
        static::created(function ($transaksiDo) {
            $jumlahPekerja = count($transaksiDo->pekerjas);
            if ($jumlahPekerja > 0) {
                $pendapatanPerPekerja = $transaksiDo->upah_bongkar / $jumlahPekerja;

                foreach ($transaksiDo->pekerjas as $pekerja) {
                    // Update pivot table
                    $transaksiDo->pekerjas()->updateExistingPivot($pekerja->id, [
                        'pendapatan_pekerja' => $pendapatanPerPekerja
                    ]);

                    // Update kolom pendapatan di tabel pekerja
                    $totalPendapatan = $pekerja->transaksiDos()
                        ->whereNull('deleted_at')
                        ->sum('pekerja_transaksi_do.pendapatan_pekerja');

                    $pekerja->update(['pendapatan' => $totalPendapatan]);
                }
            }
        });


        // Saat transaksi diupdate
        static::updated(function ($transaksiDo) {
            if ($transaksiDo->isDirty('upah_bongkar') || $transaksiDo->isDirty('pekerjas')) {
                $transaksiDo->updatePendapatanPekerja();
            }
        });

        // Saat transaksi diupdate
        static::updated(function ($transaksiDo) {
            if ($transaksiDo->isDirty('upah_bongkar')) {
                $jumlahPekerja = count($transaksiDo->pekerjas);
                if ($jumlahPekerja > 0) {
                    $pendapatanPerPekerja = $transaksiDo->upah_bongkar / $jumlahPekerja;

                    foreach ($transaksiDo->pekerjas as $pekerja) {
                        // Update pivot table
                        $transaksiDo->pekerjas()->updateExistingPivot($pekerja->id, [
                            'pendapatan_pekerja' => $pendapatanPerPekerja
                        ]);

                        // Hitung ulang total pendapatan
                        $totalPendapatan = $pekerja->transaksiDos()
                            ->whereNull('deleted_at')
                            ->sum('pekerja_transaksi_do.pendapatan_pekerja');

                        $pekerja->update(['pendapatan' => $totalPendapatan]);
                    }
                }
            }
        });

        // Saat transaksi dihapus
        static::deleting(function ($transaksiDo) {
            foreach ($transaksiDo->pekerjas as $pekerja) {
                // Update kolom pendapatan di tabel pekerja (kurangi pendapatan dari transaksi ini)
                $pendapatanDariTransaksiIni = $pekerja->pivot->pendapatan_pekerja;
                $totalPendapatan = $pekerja->pendapatan - $pendapatanDariTransaksiIni;
                $pekerja->update(['pendapatan' => max(0, $totalPendapatan)]);
            }
            // Hapus relasi di pivot table
            $transaksiDo->pekerjas()->detach();
        });
    }
}
