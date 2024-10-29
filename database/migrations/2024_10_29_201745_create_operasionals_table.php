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
        Schema::create('operasionals', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('Operasional', ['bahan_bakar', 'transportasi', 'perawatan', 'gaji', 'gudang', 'alat_dan_bahan']);
            $table->string('nama_operasional');
            $table->decimal('nominal', 15, 0);
            $table->string('keterangan');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operasionals');
    }
};
