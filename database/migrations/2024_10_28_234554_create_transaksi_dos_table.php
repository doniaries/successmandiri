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
        Schema::create('transaksi_dos', function (Blueprint $table) {
            $table->id();
            // Informasi Utama
            $table->foreignId('perusahaan_id')
                ->constrained('perusahaans')
                ->onDelete('restrict') // Mencegah penghapusan perusahaan jika masih ada transaksi
                ->comment('Perusahaan tidak bisa dihapus jika masih memiliki transaksi');
            $table->string('nomor')->unique();
            $table->date('tanggal');

            // Informasi Penjual dan Kendaraan
            $table->foreignId('penjual_id')
                ->constrained('penjuals')
                ->onDelete('restrict') // Mencegah penghapusan penjual jika masih ada transaksi
                ->comment('Penjual tidak bisa dihapus jika masih memiliki transaksi');
            $table->string('nomor_polisi')->nullable();

            // Informasi Tonase dan Harga
            $table->decimal('tonase', 15, 1)->default(0)->comment('Berat dalam ton');
            $table->decimal('harga_satuan', 15, 0)->default(0)->comment('Harga per ton');
            $table->decimal('total', 15, 1)->default(0)->comment('Total = tonase * harga_satuan');
            $table->decimal('upah_bongkar', 15, 0)->default(0);

            // Informasi Hutang
            $table->enum('jenis_hutang', ['penjual', 'pekerja'])->nullable()
                ->comment('Jenis hutang yang dibayar');
            $table->foreignId('penjual_hutang_id')->nullable()
                ->constrained('penjuals')
                ->onDelete('restrict')
                ->comment('ID penjual jika pembayaran hutang untuk penjual');
            $table->foreignId('pekerja_hutang_id')->nullable()
                ->constrained('pekerjas')
                ->onDelete('restrict')
                ->comment('ID pekerja jika pembayaran hutang untuk pekerja');
            $table->decimal('bayar_hutang', 15, 0)->default(0)->comment('Jumlah hutang yang dibayar');
            $table->decimal('sisa_bayar', 15, 0)->default(0)->comment('Sisa yang harus dibayar');

            // Informasi Pembayaran
            $table->enum('cara_bayar', ['Transfer', 'Tunai'])->default('Tunai');
            $table->string('file_do')->nullable()->comment('File bukti DO');
            $table->text('catatan')->nullable()->comment('Catatan tambahan transaksi');

            // Status Fields
            $table->enum('status', ['draft', 'final', 'batal'])->default('draft');
            $table->boolean('is_active')->default(true)->comment('Status aktif transaksi, alternatif soft delete');

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes untuk optimasi query
            $table->index(['tanggal', 'status', 'is_active']);
            $table->index(['penjual_id', 'tanggal']);
            $table->index(['jenis_hutang', 'penjual_hutang_id']);
            $table->index(['jenis_hutang', 'pekerja_hutang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_dos');
    }
};