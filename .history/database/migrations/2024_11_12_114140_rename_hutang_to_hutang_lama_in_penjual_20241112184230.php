<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penjuals', function (Blueprint $table) {
            // Rename kolom hutang menjadi hutang_lama
            $table->renameColumn('hutang', 'hutang_lama');

            // Update tipe data dan atribut jika diperlukan
            $table->decimal('hutang_lama', 15, 0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjuals', function (Blueprint $table) {
            // Kembalikan nama kolom ke hutang
            $table->renameColumn('hutang_lama', 'hutang');

            // Kembalikan tipe data dan atribut jika diperlukan
            $table->decimal('hutang', 15, 0)->change();
        });
    }
};
