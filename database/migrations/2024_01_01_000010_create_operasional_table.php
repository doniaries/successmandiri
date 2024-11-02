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
        Schema::create('operasional', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->enum('operasional', [
                'bahan_bakar',
                'transportasi',
                'perawatan',
                'gaji',
                'pinjaman',
                'isi_saldo'
            ])->index();
            $table->string('atas_nama')->index();
            $table->decimal('nominal', 15, 0)->index();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index untuk reporting
            $table->index(['tanggal', 'operasional']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operasional');
    }
};
