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
        Schema::create('pabriks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();
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
        Schema::dropIfExists('pabriks');
    }
};
