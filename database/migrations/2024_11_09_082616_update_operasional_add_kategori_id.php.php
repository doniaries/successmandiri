<?php
// update_operasional_add_kategori_id.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operasional', function (Blueprint $table) {
            // Tambah kolom baru dulu
            $table->unsignedBigInteger('kategori_id')->nullable()->after('operasional');

            // Baru tambahkan foreign key
            $table->foreign('kategori_id')
                ->references('id')
                ->on('kategori_operasional')
                ->onDelete('set null');
        });

        // Jika ada data lama yang perlu dimigrasi
        if (Schema::hasColumn('operasional', 'kategori')) {
            // Migrasi data dari enum ke foreign key
            DB::statement("
                UPDATE operasional
                SET kategori_id = (
                    SELECT id FROM kategori_operasional
                    WHERE nama = operasional.kategori
                    LIMIT 1
                )
            ");

            // Hapus kolom lama
            Schema::table('operasional', function (Blueprint $table) {
                $table->dropColumn('kategori');
            });
        }
    }

    public function down(): void
    {
        Schema::table('operasional', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
            // Kembalikan kolom kategori yang lama jika perlu
            $table->enum('kategori', ['pinjaman', 'belanja'])->after('operasional');
        });
    }
};
