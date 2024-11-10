<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop dulu tabel yang lama
        Schema::dropIfExists('operasional');

        // Buat ulang tabel dengan struktur yang benar
        Schema::create('operasional', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->enum('operasional', ['pemasukan', 'pengeluaran']);
            $table->foreignId('kategori_id')
                ->nullable()
                ->constrained('kategori_operasional')
                ->nullOnDelete();

            $table->enum('tipe_nama', ['penjual', 'user']);

            $table->foreignId('penjual_id')
                ->nullable()
                ->constrained('penjuals')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('nominal', 15, 0);
            $table->text('keterangan')->nullable();
            $table->string('file_bukti', 512)->nullable();

            // Tracking
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tanggal');
            $table->index('operasional');
            $table->index('nominal');
            $table->index('tipe_nama');
            $table->index(['tanggal', 'operasional']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operasional');
    }
};
