<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'perusahaan_id')) {
                $table->foreignId('perusahaan_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('perusahaans')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'perusahaan_id')) {
                $table->dropForeign(['perusahaan_id']);
                $table->dropColumn('perusahaan_id');
            }
        });
    }
};
