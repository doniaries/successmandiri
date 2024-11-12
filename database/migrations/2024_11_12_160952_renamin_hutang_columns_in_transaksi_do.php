<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_do', function (Blueprint $table) {
            // Rename kolom hutang menjadi hutang_awal
            $table->renameColumn('hutang', 'hutang_awal');
            // Rename untuk konsistensi naming
            $table->renameColumn('bayar_hutang', 'pembayaran_hutang');
            $table->renameColumn('sisa_hutang', 'sisa_hutang_penjual');
        });

        // Tambah kolom di riwayat_hutang untuk referensi ke transaksi_do
        Schema::table('riwayat_hutang', function (Blueprint $table) {
            $table->unsignedBigInteger('transaksi_do_id')->nullable()->after('operasional_id');
            $table->decimal('hutang_sebelum', 15, 0)->after('nominal');
            $table->decimal('hutang_sesudah', 15, 0)->after('hutang_sebelum');

            $table->foreign('transaksi_do_id')
                ->references('id')
                ->on('transaksi_do')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->renameColumn('hutang_awal', 'hutang');
            $table->renameColumn('pembayaran_hutang', 'bayar_hutang');
            $table->renameColumn('sisa_hutang_penjual', 'sisa_hutang');
        });

        Schema::table('riwayat_hutang', function (Blueprint $table) {
            $table->dropForeign(['transaksi_do_id']);
            $table->dropColumn(['transaksi_do_id', 'hutang_sebelum', 'hutang_sesudah']);
        });
    }
};
