<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'hutang',
        'bayar_hutang',
        'sisa_hutang',
        'sisa_bayar',
        'file_do',
        'cara_bayar',
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
        'hutang' => 'integer',
        'bayar_hutang' => 'integer',
        'sisa_hutang' => 'integer',
        'sisa_bayar' => 'integer',
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

    public function updatePendapatanPekerja()
    {
        $jumlahPekerja = $this->pekerjas()->count();
        if ($jumlahPekerja > 0) {
            $pendapatanPerPekerja = $this->upah_bongkar / $jumlahPekerja;

            $this->pekerjas()->each(function ($pekerja) use ($pendapatanPerPekerja) {
                $pekerja->increment('pendapatan', $pendapatanPerPekerja);
                $this->pekerjas()->updateExistingPivot($pekerja->id, [
                    'pendapatan_pekerja' => $pendapatanPerPekerja
                ]);
            });
        }
    }
}
