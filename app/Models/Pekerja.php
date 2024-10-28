<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pekerja extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'pendapatan',
        'hutang',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'pendapatan' => 'decimal:2',
        'hutang' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relasi ke user yang membuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke user yang mengupdate
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scope untuk data aktif
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}