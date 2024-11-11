<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('laporan_keuangan', function (Blueprint $table) {
            $table->id();
            $table->datetime('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);

            // Memisahkan jenis laporan
            $table->enum('tipe_transaksi', ['transaksi_do', 'operasional'])
                ->comment('Membedakan sumber transaksi: DO atau Operasional');

            // Kategori spesifik sesuai tipe
            $table->enum('kategori_do', [
                'bayar_hutang',     // Uang masuk dari pembayaran hutang
                'biaya_lain',       // Pengeluaran biaya lain DO
                'upah_bongkar',     // Pengeluaran upah bongkar
                'pembayaran_do',    // Pengeluaran sisa bayar DO
            ])->nullable();

            $table->unsignedBigInteger('kategori_operasional_id')->nullable();
            $table->foreign('kategori_operasional_id')
                ->references('id')
                ->on('kategori_operasional')
                ->onDelete('cascade');

            $table->string('keterangan')->nullable();
            $table->decimal('nominal', 15, 0);
            $table->decimal('saldo_sebelum', 15, 0);
            $table->decimal('saldo_sesudah', 15, 0);

            // Relasi ke transaksi
            $table->unsignedBigInteger('transaksi_do_id')->nullable();
            $table->foreign('transaksi_do_id')
                ->references('id')
                ->on('transaksi_do')
                ->onDelete('cascade');

            $table->unsignedBigInteger('operasional_id')->nullable();
            $table->foreign('operasional_id')
                ->references('id')
                ->on('operasional')
                ->onDelete('cascade');

            // Metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamps();

            // Indexes untuk performa query
            $table->index('tanggal');
            $table->index(['tipe_transaksi', 'jenis']);
            $table->index('transaksi_do_id');
            $table->index('operasional_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('laporan_keuangan');
    }
};
