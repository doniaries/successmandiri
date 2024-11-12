<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            // Tambah kolom setelah kolom transaksi_do_id
            $table->string('nomor_transaksi')->nullable()->after('kategori_operasional_id');
            $table->string('nama_penjual')->nullable()->after('nomor_transaksi');

            // Tambah index untuk pencarian
            $table->index('nomor_transaksi');
            $table->index('nama_penjual');
        });

        // Update data yang sudah ada
        DB::statement("
            UPDATE laporan_keuangan lk
            LEFT JOIN transaksi_do td ON lk.transaksi_do_id = td.id
            LEFT JOIN penjuals p ON td.penjual_id = p.id
            SET
                lk.nomor_transaksi = td.nomor,
                lk.nama_penjual = p.nama
            WHERE lk.tipe_transaksi = 'transaksi_do'
        ");
    }

    public function down()
    {
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            $table->dropIndex(['nomor_transaksi']);
            $table->dropIndex(['nama_penjual']);
            $table->dropColumn(['nomor_transaksi', 'nama_penjual']);
        });
    }
};
