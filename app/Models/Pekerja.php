<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pekerja extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'pekerjas';

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'pendapatan',
        'hutang',
        // 'created_by',
        // 'updated_by',
    ];

    protected $dates = [
        'deleted_at',
    ];


    protected $casts = [
        'pendapatan' => 'decimal:0',
        'hutang' => 'decimal:0',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];



    // Scope untuk data aktif
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
