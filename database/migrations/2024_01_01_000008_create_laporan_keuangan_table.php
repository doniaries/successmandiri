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
        Schema::create('laporan_keuangan', function (Blueprint $table) {
            $table->id();
            $table->datetime('tanggal');
            $table->enum('jenis_transaksi', ['Pemasukan', 'Pengeluaran']);
            $table->string('kategori', 50)->comment('Kategori transaksi (DO/Operasional)');
            $table->string('sub_kategori', 50)->nullable()->comment('Sub kategori seperti upah_bongkar, biaya_lain, dll');
            $table->decimal('nominal', 15, 0);
            $table->string('sumber_transaksi', 50)->comment('DO/Operasional');
            $table->unsignedBigInteger('referensi_id')->comment('ID dari tabel sumber (transaksi_do/operasional)');
            $table->string('nomor_referensi', 50)->nullable()->comment('Nomor DO jika dari transaksi DO');
            $table->string('pihak_terkait', 100)->nullable()->comment('Nama penjual/user terkait');
            $table->enum('tipe_pihak', ['penjual', 'user'])->nullable();
            $table->string('cara_pembayaran', 20)->nullable()->comment('Tunai/Transfer/Cair di Luar');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tanggal');
            $table->index('jenis_transaksi');
            $table->index('kategori');
            $table->index(['sumber_transaksi', 'referensi_id']);
            $table->index('nominal');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_keuangan');
    }
};
