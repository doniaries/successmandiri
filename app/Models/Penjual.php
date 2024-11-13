<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penjual extends Model
{
    use HasFactory, SoftDeletes;

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


    public function transaksiDo(): HasMany
    {
        return $this->hasMany(TransaksiDo::class);
    }

    public function updateHutang(float $amount, string $type = 'add'): void
    {
        if ($type === 'add') {
            $this->increment('hutang', $amount);
        } else {
            $this->decrement('hutang', $amount);
        }
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
