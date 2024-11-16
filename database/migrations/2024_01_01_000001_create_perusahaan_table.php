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
            $table->string('name')->unique();
            $table->decimal('saldo', 15, 0)->default(0);
            $table->string('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('pimpinan')->nullable()->comment('Pimpinan Perusahaan');
            $table->string('npwp', 30)->nullable();
            $table->boolean('is_active')->default(true)->comment('Status aktif perusahaan');
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
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
