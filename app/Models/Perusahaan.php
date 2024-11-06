<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perusahaan extends Model
{

    use HasFactory, SoftDeletes;
    protected $fillable = [
        'nama',
        'alamat',
        'pimpinan',
        'is_active',
        'saldo',
        'kasir_id',
    ];


    protected $casts = [
        'saldo' => 'decimal:0',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
