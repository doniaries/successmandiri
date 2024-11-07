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
        Schema::create('keuangans', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->enum('jenis', ['pemasukan', 'pengeluaran']);
            $table->enum('kategori', ['transaksi_do', 'bayar_hutang', 'lainnya']);
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('nominal', 15, 0);
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tanggal');
            $table->index('jenis');
            $table->index('kategori');
            $table->index('referensi_id');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuangan');
    }
};
