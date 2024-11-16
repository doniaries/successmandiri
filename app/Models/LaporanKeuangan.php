<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Operasional, TransaksiDo};
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\{LaporanKeuanganTrait, DokumentasiTrait};

class LaporanKeuangan extends Model
{
    use SoftDeletes, LaporanKeuanganTrait, DokumentasiTrait;

    protected $table = 'laporan_keuangan';

    protected $fillable = [
        'tanggal',
        'jenis_transaksi', // Sesuaikan dengan kolom di database
        'kategori',        // Sesuaikan nama kolom
        'sub_kategori',
        'nominal',
        'sumber_transaksi',
        'referensi_id',
        'nomor_referensi',
        'pihak_terkait',
        'tipe_pihak',
        'cara_pembayaran',
        'keterangan',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:0',
        'saldo_sebelum' => 'decimal:0',
        'saldo_sesudah' => 'decimal:0',
        'mempengaruhi_kas' => 'boolean'
    ];

    // Relations

    public function transaksiDo()
    {
        return $this->belongsTo(TransaksiDo::class);
    }


    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function operasional()
    {
        return $this->belongsTo(Operasional::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePemasukan($query)
    {
        return $query->where('jenis', 'masuk');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('jenis', 'keluar');
    }

    public function scopeFromDO($query)
    {
        return $query->where('tipe_transaksi', 'transaksi_do');
    }

    public function scopeFromOperasional($query)
    {
        return $query->where('tipe_transaksi', 'operasional');
    }

    public function scopeAffectsCash($query)
    {
        return $query->where('mempengaruhi_kas', true);
    }
}
