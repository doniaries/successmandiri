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
            $table->dateTime('tanggal');

            // Jenis transaksi (masuk/keluar)
            $table->enum('jenis', ['masuk', 'keluar']);

            // Sumber transaksi (DO/Operasional)
            $table->enum('tipe_transaksi', ['transaksi_do', 'operasional'])
                ->comment('Membedakan sumber transaksi: DO atau Operasional');

            // Jika sumber dari DO
            $table->foreignId('transaksi_do_id')
                ->nullable()
                ->constrained('transaksi_do')
                ->cascadeOnDelete();

            $table->enum('kategori_do', [
                'bayar_hutang',    // Pemasukan dari pembayaran hutang penjual
                'biaya_lain',      // Pengeluaran biaya lain di DO
                'upah_bongkar',    // Pengeluaran upah bongkar
                'pembayaran_do'    // Pengeluaran pembayaran DO ke penjual
            ])->nullable();

            // Jika sumber dari Operasional
            $table->foreignId('operasional_id')
                ->nullable()
                ->constrained('operasional')
                ->cascadeOnDelete();

            $table->foreignId('kategori_operasional_id')
                ->nullable()
                ->constrained('kategori_operasional')
                ->cascadeOnDelete();

            // Nominal dan Saldo
            $table->decimal('nominal', 15, 0)->default(0);
            $table->decimal('saldo_sebelum', 15, 0)->default(0);
            $table->decimal('saldo_sesudah', 15, 0)->default(0);

            // Info tambahan
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes untuk optimasi query
            $table->index('tanggal');
            $table->index(['tipe_transaksi', 'jenis']);
            $table->index('kategori_do');
            $table->index('kategori_operasional_id');
            $table->index('transaksi_do_id');
            $table->index('operasional_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('laporan_keuangan');
    }
};
