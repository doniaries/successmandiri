<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): ?Perusahaan
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing records
        DB::table('users')->where('perusahaan_id', '!=', null)->delete();
        Perusahaan::truncate();

        // Create the main perusahaan
        $perusahaan = Perusahaan::create([
            'name' => 'CV SUCCESS MANDIRI',
            'alamat' => 'Dusun Sungai Moran Nagari Kamang',
            'telepon' => '+62 823-8921-9670',
            'pimpinan' => 'Yondra',
            'npwp' => '12.345.678.9-123.000',
            'saldo' => 100000000,
            'is_active' => true,

        ]);

        // Create additional perusahaan
        Perusahaan::create([
            'name' => 'Koperasi Success Mandiri',
            'alamat' => 'Sungai Moran, Nagari Kamang',
            'telepon' => '+62 852-7845-1122',
            'pimpinan' => 'Yondra',
            'npwp' => '12.345.678.9-124.000',
            'saldo' => 150000000,
            'is_active' => true,

        ]);

        Perusahaan::create([
            'name' => 'CV SUCCESS',
            'alamat' => 'Sungai Moran, Nagari Kamang',
            'telepon' => '+62 813-6677-8899',
            'pimpinan' => 'Yondra',
            'npwp' => '12.345.678.9-125.000',
            'saldo' => 120000000,
            'is_active' => true,

        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $perusahaan;
    }
}
