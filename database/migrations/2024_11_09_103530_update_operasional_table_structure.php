<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operasional', function (Blueprint $table) {
            // Drop kolom yang akan dimodifikasi
            $table->dropColumn([
                'operasional',
                'tipe_nama'
            ]);

            // Tambah kolom baru dengan definisi yang benar
            $table->enum('operasional', ['pemasukan', 'pengeluaran'])->after('tanggal');
            $table->enum('tipe_nama', ['penjual', 'user'])->after('kategori_id');

            // Tambah index baru
            $table->index('operasional');
            $table->index('tipe_nama');
        });
    }

    public function down(): void
    {
        Schema::table('operasional', function (Blueprint $table) {
            // Rollback perubahan jika perlu
            $table->dropIndex(['operasional']);
            $table->dropIndex(['tipe_nama']);

            $table->dropColumn([
                'operasional',
                'tipe_nama'
            ]);

            $table->enum('operasional', ['pinjaman', 'belanja'])->after('tanggal');
            $table->string('tipe_nama')->after('kategori_id');
        });
    }
};
