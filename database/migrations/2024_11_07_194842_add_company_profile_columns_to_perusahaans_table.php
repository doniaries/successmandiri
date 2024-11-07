<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            // Identitas Visual
            if (!Schema::hasColumn('perusahaans', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('nama');
            }
            if (!Schema::hasColumn('perusahaans', 'favicon_path')) {
                $table->string('favicon_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('perusahaans', 'tema_warna')) {
                $table->string('tema_warna', 20)->nullable()->after('favicon_path');
            }

            // Informasi Kontak
            if (!Schema::hasColumn('perusahaans', 'email')) {
                $table->string('email')->nullable()->after('alamat');
            }
            if (!Schema::hasColumn('perusahaans', 'telepon')) {
                $table->string('telepon')->nullable()->after('email');
            }
            if (!Schema::hasColumn('perusahaans', 'website')) {
                $table->string('website')->nullable()->after('telepon');
            }

            // Informasi Bisnis
            if (!Schema::hasColumn('perusahaans', 'saldo')) {
                $table->decimal('saldo', 15, 0)->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('perusahaans', 'npwp')) {
                $table->string('npwp', 30)->nullable()->after('saldo');
            }
            if (!Schema::hasColumn('perusahaans', 'no_izin_usaha')) {
                $table->string('no_izin_usaha', 50)->nullable()->after('npwp');
            }
            if (!Schema::hasColumn('perusahaans', 'setting')) {
                $table->json('setting')->nullable()->after('no_izin_usaha');
            }

            // Tambah indeks jika belum ada
            if (!$this->hasIndex('perusahaans', 'perusahaans_email_index')) {
                $table->index('email');
            }
            if (!$this->hasIndex('perusahaans', 'perusahaans_telepon_index')) {
                $table->index('telepon');
            }
            if (!$this->hasIndex('perusahaans', 'perusahaans_npwp_index')) {
                $table->index('npwp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            // Hapus indexes
            if ($this->hasIndex('perusahaans', 'perusahaans_email_index')) {
                $table->dropIndex(['email']);
            }
            if ($this->hasIndex('perusahaans', 'perusahaans_telepon_index')) {
                $table->dropIndex(['telepon']);
            }
            if ($this->hasIndex('perusahaans', 'perusahaans_npwp_index')) {
                $table->dropIndex(['npwp']);
            }

            // Hapus kolom-kolom
            $columns = [
                'logo_path',
                'favicon_path',
                'tema_warna',
                'email',
                'telepon',
                'website',
                'saldo',
                'npwp',
                'no_izin_usaha',
                'setting'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('perusahaans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex($table, $indexName): bool
    {
        $indexes = DB::select(
            "SHOW INDEX FROM {$table} WHERE Key_name = ?",
            [$indexName]
        );

        return count($indexes) > 0;
    }
};
