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
        Schema::create('pekerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->decimal('pendapatan', 15, 0)->default(0);
            $table->decimal('hutang', 15, 0)->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('nama');
            $table->index('telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pekerjas');
    }
};
