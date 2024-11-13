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
        Schema::dropIfExists('riwayat_hutang');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('riwayat_hutang', function (Blueprint $table) {
            $table->id();
            $table->string('tipe_entitas');
            $table->unsignedBigInteger('entitas_id');
            $table->decimal('nominal', 15, 2);
            $table->string('jenis');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('transaksi_do_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
