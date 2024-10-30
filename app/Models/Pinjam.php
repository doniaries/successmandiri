<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pinjam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tanggal_pinjaman',
        'kategori_peminjam',
        'peminjam_id',
        'nominal',
        'deskripsi',
    ];


    public function peminjam()
    {
        return $this->morphTo('peminjam', 'kategori_peminjam', 'peminjam_id');
    }
}