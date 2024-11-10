<?php
// create_kategori_operasional_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_operasional', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenis', ['pemasukan', 'pengeluaran']);
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert data default
        $now = now();
        DB::table('kategori_operasional')->insert([
            [
                'nama' => 'Pinjaman',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Pemberian pinjaman',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Uang Jalan',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Uang operasional transportasi',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Bahan Bakar',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Pembelian BBM',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Perawatan',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Maintenance & perbaikan',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Gaji',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Penggajian karyawan',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Bayar Hutang',
                'jenis' => 'pemasukan',
                'keterangan' => 'Pembayaran hutang',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nama' => 'Lain-lain',
                'jenis' => 'pemasukan',
                'keterangan' => 'Pemasukan lainnya',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_operasional');
    }
};
