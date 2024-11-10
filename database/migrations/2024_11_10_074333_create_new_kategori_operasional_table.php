<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus dulu kategori 'Total DO' jika ada karena tidak diperlukan lagi
        DB::table('kategori_operasional')
            ->where('nama', 'Total DO')
            ->delete();

        // Tambahkan kategori baru
        $kategoriBaru = [
            [
                'nama' => 'Pemasukan DO',
                'jenis' => 'pemasukan',
                'keterangan' => 'Pemasukan dari penjual untuk komponen DO (upah bongkar, biaya lain, bayar hutang)',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'nama' => 'Biaya Operasional DO',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Biaya operasional lain terkait DO',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nama' => 'Pembayaran DO',
                'jenis' => 'pengeluaran',
                'keterangan' => 'Pembayaran sisa DO ke penjual',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('kategori_operasional')->insert($kategoriBaru);

        // Pastikan ada kolom is_from_transaksi di tabel operasional
        if (!Schema::hasColumn('operasional', 'is_from_transaksi')) {
            Schema::table('operasional', function (Blueprint $table) {
                $table->boolean('is_from_transaksi')
                    ->default(false)
                    ->comment('Flag untuk menandai data dari transaksi DO');

                // Tambahkan index untuk performa query
                $table->index('is_from_transaksi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus kategori yang ditambahkan
        DB::table('kategori_operasional')
            ->whereIn('nama', [
                'Pemasukan DO',
                'Upah Bongkar',
                'Biaya Operasional DO',
                'Pembayaran DO'
            ])
            ->delete();

        // Kembalikan kategori 'Total DO' jika diperlukan
        DB::table('kategori_operasional')->insert([
            'nama' => 'Total DO',
            'jenis' => 'pengeluaran',
            'keterangan' => 'Nilai Total DO',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Hapus kolom is_from_transaksi jika ada
        if (Schema::hasColumn('operasional', 'is_from_transaksi')) {
            Schema::table('operasional', function (Blueprint $table) {
                $table->dropIndex(['is_from_transaksi']);
                $table->dropColumn('is_from_transaksi');
            });
        }
    }
};
