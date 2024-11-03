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
        Schema::create('pekerja_transaksi_do', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_do_id')->constrained('transaksi_do')->cascadeOnDelete();
            $table->foreignId('pekerja_id')->constrained('pekerjas')->cascadeOnDelete();
            $table->decimal('pendapatan_pekerja', 15, 0)->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['transaksi_do_id', 'pekerja_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pekerja_transaksi_do');
    }
};
