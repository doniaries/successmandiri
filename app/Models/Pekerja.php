<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pekerja extends Model
{
    use SoftDeletes;
    protected $table = 'pekerja';

    protected $fillable = [
        'id',
        'nama',
        'alamat',
        'telepon',
        'pendapatan',
        'hutang',
        'team_id',
    ];

    protected $casts = [
        'pendapatan' => 'decimal:0',
        'hutang' => 'decimal:0',
    ];


    public function operasional()
    {
        return $this->hasMany(Operasional::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
