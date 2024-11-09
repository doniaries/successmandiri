<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->string('kode_perusahaan', 10)->unique();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('tema_warna', 20)->default('amber');
            $table->string('alamat')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('nama_pimpinan')->nullable();
            $table->string('hp_pimpinan')->nullable();
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('npwp', 25)->nullable();
            $table->string('no_izin_usaha', 50)->nullable();
            $table->decimal('saldo', 15, 0)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('keterangan')->nullable();
            $table->json('pengaturan')->nullable()->comment('Pengaturan tambahan dalam format JSON');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['nama_perusahaan', 'kode_perusahaan']);
            $table->index('email');
            $table->index('telepon');
            $table->index('npwp');
        });

        // Tambahkan kolom setting_id ke semua tabel yang relevan
        $tables = [
            'users',
            'penjuals',
            'pekerjas',
            'transaksi_do',
            'operasional'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('setting_id')
                    ->after('id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Hapus foreign key dari semua tabel terkait
        $tables = [
            'users',
            'penjuals',
            'pekerjas',
            'transaksi_do',
            'operasional'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['setting_id']);
                $table->dropColumn('setting_id');
            });
        }

        Schema::dropIfExists('settings');
    }
};