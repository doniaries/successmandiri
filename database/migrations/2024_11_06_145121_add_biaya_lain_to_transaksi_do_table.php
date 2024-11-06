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
            $table->decimal('biaya_lain', 15, 0)->default(0)->after('upah_bongkar');
            $table->string('keterangan_biaya_lain')->nullable()->after('biaya_lain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->dropColumn(['biaya_lain', 'keterangan_biaya_lain']);
        });
    }
};
