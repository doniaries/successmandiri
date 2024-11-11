<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjual extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'hutang',
    ];

    protected $casts = [
        'hutang' => 'decimal:0',
    ];

    public function riwayatHutang()
    {
        return $this->hasMany(RiwayatHutang::class, 'entitas_id')
            ->where('tipe_entitas', 'penjual');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($penjual) {
            if ($penjual->isDirty('hutang')) {
                \Log::info('Perubahan Hutang Penjual:', [
                    'penjual' => $penjual->nama,
                    'hutang_lama' => $penjual->getOriginal('hutang'),
                    'hutang_baru' => $penjual->hutang,
                    'selisih' => $penjual->hutang - $penjual->getOriginal('hutang')
                ]);
            }
        });
    }
}