<?php

namespace App\Models;

use App\Models\Team;
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
        'team_id',
    ];

    protected $casts = [
        'hutang' => 'decimal:0',
    ];

    // Custom accessor for formatted hutang
    public function getFormattedHutangAttribute()
    {
        return 'Rp ' . number_format($this->hutang, 0, ',', '.');
    }

    // Relationships with optimized queries
    public function transaksiDo(): HasMany
    {
        return $this->hasMany(TransaksiDo::class)
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
            ->withSum('transaksiDo', 'total');
    }

    public function scopeHasHutang($query)
    {
        return $query->where('hutang', '>', 0);
    }

    public function paymentHistory()
    {
        return $this->hasMany(TransaksiDo::class, 'penjual_id')
            ->select('id', 'pembayaran_hutang', 'created_at')
            ->orderBy('created_at', 'desc');
    }


    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
