<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Keuangan extends Model
{
    protected $table = 'combined_keuangan_view';
    public $timestamps = false;
    protected $primaryKey = 'unique_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted()
    {
        static::addGlobalScope('base_query', function ($query) {
            return $query->from(DB::raw('(' . self::baseQuery()->toSql() . ') as combined_keuangan_view'))
                ->mergeBindings(self::baseQuery());
        });
    }

    public static function baseQuery()
    {
        // Query untuk TransaksiDo
        $transactionQuery = DB::table('transaksi_do')
            ->join('penjuals', 'transaksi_do.penjual_id', '=', 'penjuals.id')
            ->whereNull('transaksi_do.deleted_at')
            ->select([
                DB::raw("CONCAT('TRX-', transaksi_do.id) as unique_id"),
                'transaksi_do.tanggal',
                DB::raw("'transaksi_do' as sumber"),
                DB::raw("CASE
                    WHEN transaksi_do.sisa_bayar > 0 THEN 'pemasukan'
                    ELSE 'pengeluaran'
                END as jenis"),
                'transaksi_do.total as nominal',
                DB::raw("CONCAT('Transaksi DO - ', penjuals.nama) as keterangan"),
                'transaksi_do.created_at'
            ]);

        // Query untuk Operasional
        $operationalQuery = DB::table('operasional')
            ->whereNull('deleted_at')
            ->select([
                DB::raw("CONCAT('OPR-', operasional.id) as unique_id"),
                'tanggal',
                DB::raw("'operasional' as sumber"),
                DB::raw("CASE
                    WHEN operasional = 'pinjaman' THEN 'pengeluaran'
                    WHEN operasional = 'bayar_hutang' THEN 'pemasukan'
                    WHEN operasional = 'isi_saldo' THEN 'pemasukan'
                    ELSE 'pengeluaran'
                END as jenis"),
                'nominal',
                DB::raw("CONCAT(
                    CASE
                        WHEN operasional = 'isi_saldo' THEN 'Penambahan Saldo - '
                        WHEN operasional = 'pinjaman' THEN 'Pinjaman - '
                        WHEN operasional = 'bayar_hutang' THEN 'Pembayaran Hutang - '
                        WHEN operasional = 'bahan_bakar' THEN 'Bahan Bakar - '
                        WHEN operasional = 'transportasi' THEN 'Transportasi - '
                        WHEN operasional = 'perawatan' THEN 'Perawatan - '
                        WHEN operasional = 'gaji' THEN 'Gaji - '
                        ELSE CONCAT(UPPER(SUBSTRING(operasional, 1, 1)), LOWER(SUBSTRING(operasional, 2)), ' - ')
                    END,
                    atas_nama
                ) as keterangan"),
                'created_at'
            ]);

        return $transactionQuery->unionAll($operationalQuery);
    }
}
