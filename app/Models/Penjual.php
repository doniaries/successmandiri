<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penjual extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['riwayatHutangTerbaru']; // Eager load riwayat hutang terbaru

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
            ->where('tipe_entitas', 'penjual')
            ->select(['id', 'entitas_id', 'nominal', 'jenis', 'created_at'])
            ->latest();
    }

    public function riwayatHutangTerbaru()
    {
        return $this->riwayatHutang()->take(5);
    }

    // Custom accessor for formatted hutang
    public function getFormattedHutangAttribute()
    {
        return 'Rp ' . number_format($this->hutang, 0, ',', '.');
    }

    // Relationships with optimized queries
    public function transaksiDo()
    {
        return $this->hasMany(TransaksiDo::class)
            ->select(['id', 'penjual_id', 'nomor', 'tanggal', 'total', 'status_bayar'])
            ->latest();
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

    // Scopes
    public function scopeWithTransaksiStats($query)
    {
        return $query->withCount('transaksiDo')
            ->withSum('transaksiDo', 'total')
            ->withSum('riwayatHutang', 'nominal');
    }

    public function scopeHasHutang($query)
    {
        return $query->where('hutang', '>', 0);
    }
}
