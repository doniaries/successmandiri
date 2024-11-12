<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('riwayat_hutang', function (Blueprint $table) {
            $table->decimal('hutang_sebelum', 15, 0)->default(0)->change();
            $table->decimal('hutang_sesudah', 15, 0)->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('riwayat_hutang', function (Blueprint $table) {
            $table->decimal('hutang_sebelum', 15, 0)->change();
            $table->decimal('hutang_sesudah', 15, 0)->change();
        });
    }
};
