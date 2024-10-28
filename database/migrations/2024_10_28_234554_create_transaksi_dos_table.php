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
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->foreignId('penjual_id')
                ->constrained('penjuals')
                ->onDelete('restrict');
            $table->string('nomor_polisi')->nullable();
            $table->decimal('tonase', 15, 2)->default(0);
            $table->decimal('harga_satuan', 15, 0)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('upah_bongkar', 15, 0)->default(0);

            // Informasi Hutang
            $table->foreignId('hutang')
                ->nullable()
                ->constrained('penjuals', 'id')
                ->onDelete('restrict');
            $table->decimal('bayar_hutang', 15, 0)->default(0);
            $table->decimal('sisa_bayar', 15, 0)->default(0);

            // Informasi Pembayaran dan Dokumen
            $table->string('file_do')->nullable();
            $table->enum('cara_bayar', ['Transfer', 'Tunai'])->default('Tunai');
            $table->text('catatan')->nullable();

            // Timestamps dan Soft Delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tanggal', 'penjual_id']);
            $table->index('nomor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_do');
    }
};