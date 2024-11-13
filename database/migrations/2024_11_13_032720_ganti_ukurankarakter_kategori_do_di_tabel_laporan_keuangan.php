<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            // Ubah tipe data dan panjang kolom kategori_do
            $table->string('kategori_do', 50)->change(); // Sesuaikan panjang sesuai kebutuhan
        });
    }

    public function down()
    {
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            $table->string('kategori_do')->change();
        });
    }
};
