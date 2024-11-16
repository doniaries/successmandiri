<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambah team_id ke penjuals
        Schema::table('penjuals', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained('teams');
            $table->index('team_id');
        });

        // Tambah team_id ke pekerja
        Schema::table('pekerja', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained('teams');
            $table->index('team_id');
        });

        // Tambah team_id ke operasional
        Schema::table('operasional', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained('teams');
            $table->index('team_id');
        });

        // Tambah team_id ke transaksi_do
        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained('teams');
            $table->index('team_id');
        });

        // Tambah team_id ke laporan_keuangan
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained('teams');
            $table->index('team_id');
        });
    }

    public function down()
    {
        Schema::table('penjuals', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('pekerja', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('operasional', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('transaksi_do', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('laporan_keuangan', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
