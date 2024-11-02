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
        Schema::create('perusahaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->string('pimpinan')->nullable()->comment('Pimpinan Perusahaan');
            $table->foreignId('kasir_id')->nullable()->comment('Kasir Perusahaan')->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->comment('Status aktif perusahaan');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perusahaans');
    }
};
