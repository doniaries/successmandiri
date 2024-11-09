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
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('tema_warna', 20)->nullable();
            $table->string('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('pimpinan')->nullable()->comment('Pimpinan Perusahaan');
            $table->foreignId('kasir_id')->nullable()->comment('Kasir Perusahaan')->constrained('users')->nullOnDelete();
            $table->decimal('saldo', 15, 0)->default(0);
            $table->string('npwp', 30)->nullable();
            $table->string('no_izin_usaha', 50)->nullable();
            $table->json('setting')->nullable();
            $table->boolean('is_active')->default(true)->comment('Status aktif perusahaan');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nama');
            $table->index('email');
            $table->index('telepon');
            $table->index('npwp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaans');
    }
};