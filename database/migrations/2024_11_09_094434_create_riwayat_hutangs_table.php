<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_hutang', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe_entitas', ['penjual']);
            $table->unsignedBigInteger('entitas_id');
            $table->decimal('nominal', 15, 0);
            $table->enum('jenis', ['penambahan', 'pengurangan']);
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('operasional_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tipe_entitas', 'entitas_id']);
            $table->index('operasional_id');

            // Foreign key
            $table->foreign('operasional_id')
                ->references('id')
                ->on('operasional')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_hutang');
    }
};
