<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_do', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 20);
            $table->datetime('tanggal');
            $table->foreignId('penjual_id')->constrained('penjuals');
            $table->string('nomor_polisi', 20)->nullable();
            $table->decimal('tonase', 10, 2);
            $table->decimal('harga_satuan', 15, 0);
            $table->decimal('total', 15, 0);
            $table->decimal('upah_bongkar', 15, 0);
            $table->decimal('biaya_lain', 15, 0)->default(0);
            $table->string('keterangan_biaya_lain')->nullable();
            $table->decimal('hutang', 15, 0);
            $table->decimal('bayar_hutang', 12, 0);
            $table->decimal('sisa_hutang', 12, 0);
            $table->decimal('sisa_bayar', 15, 0);
            $table->string('file_do')->nullable();
            $table->enum('cara_bayar', ['Tunai', 'Transfer'])->default('Tunai');
            $table->enum('status_bayar', ['Belum Lunas', 'Lunas']);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nomor');
            $table->index(['tanggal', 'penjual_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_do');
    }
};
