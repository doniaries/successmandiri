<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hugomyb\FilamentMediaAction\Models\Media;
use App\Traits\GenerateMonthlyNumber;
use Illuminate\Database\Eloquent\Relations\HasMany;

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


    public function penjual()
    {
        return $this->belongsTo(Penjual::class);
    }

    public function operasional(): HasMany
    {
        return $this->hasMany(Operasional::class, 'penjual_id');
    }

    protected static function boot()
    {
        parent::boot();
    }
}
