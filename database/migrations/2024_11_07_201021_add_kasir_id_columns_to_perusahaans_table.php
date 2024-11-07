<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            // Tambah kolom kasir_id jika belum ada
            if (!Schema::hasColumn('perusahaans', 'kasir_id')) {
                $table->foreignId('kasir_id')
                    ->nullable()
                    ->after('setting')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            // Hapus foreign key jika ada
            if (Schema::hasColumn('perusahaans', 'kasir_id')) {
                $table->dropForeign(['kasir_id']);
                $table->dropColumn('kasir_id');
            }
        });
    }
};
