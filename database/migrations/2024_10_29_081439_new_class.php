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
        Schema::table('transaksi_do', function (Blueprint $table) {
            // Drop foreign key lama
            $table->dropForeign('transaksi_do_hutang_foreign');

            // Ubah tipe kolom hutang
            $table->decimal('hutang', 15, 0)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->foreignId('hutang')->constrained('penjuals');
        });
    }
};
