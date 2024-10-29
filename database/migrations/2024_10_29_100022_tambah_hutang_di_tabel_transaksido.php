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
            $table->decimal('sisa_hutang', 15, 0)->default(0)->after('bayar_hutang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->dropColumn('sisa_hutang');
        });
    }
};
