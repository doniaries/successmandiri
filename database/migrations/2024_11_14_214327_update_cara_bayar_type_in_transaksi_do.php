<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `transaksi_do` MODIFY `cara_bayar` VARCHAR(20) NOT NULL DEFAULT 'Tunai'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `transaksi_do` MODIFY `cara_bayar` ENUM('Tunai', 'Transfer', 'Cair di Luar') NOT NULL DEFAULT 'Tunai'");
    }
};
