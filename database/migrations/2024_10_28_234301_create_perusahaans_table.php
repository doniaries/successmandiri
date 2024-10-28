<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->string('pimpinan')->nullable()->comment('Pimpinan Perusahaan');
            $table->foreignId('kasir_id')->nullable()->comment('Kasir Perusahaan')->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();  // Tambahkan soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaans');
    }
};