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
            'nama' => 'CV SUCCESS MANDIRI',
            'alamat' => 'Dusun Sungai Moran Nagari Kamang',
            'telepon' => '+62 823-8921-9670',
            'pimpinan' => 'Yondra',
            'npwp' => '12.345.678.9-123.000',
            'saldo' => 10000000,
            'is_active' => true,
            'tema_warna' => 'amber',
            'setting' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id',
            ], JSON_THROW_ON_ERROR)
        ]);

        // Create additional perusahaan
        Perusahaan::create([
            'nama' => 'CV KARYA MAJU BERSAMA',
            'alamat' => 'Jorong I Sungai Moran, Nagari Kamang',
            'telepon' => '+62 852-7845-1122',
            'pimpinan' => 'Fadli',
            'npwp' => '12.345.678.9-124.000',
            'saldo' => 15000000,
            'is_active' => true,
            'tema_warna' => 'blue',
            'setting' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id',
            ], JSON_THROW_ON_ERROR)
        ]);

        Perusahaan::create([
            'nama' => 'CV MITRA SEJAHTERA',
            'alamat' => 'Jorong II Muaro Tais, Nagari Kamang',
            'telepon' => '+62 813-6677-8899',
            'pimpinan' => 'Rahman',
            'npwp' => '12.345.678.9-125.000',
            'saldo' => 12000000,
            'is_active' => true,
            'tema_warna' => 'green',
            'setting' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id',
            ], JSON_THROW_ON_ERROR)
        ]);

        Perusahaan::create([
            'nama' => 'CV BERKAH JAYA',
            'alamat' => 'Jorong III Lubuk Karak, Nagari Kamang',
            'telepon' => '+62 821-3344-5566',
            'pimpinan' => 'Hendra',
            'npwp' => '12.345.678.9-126.000',
            'saldo' => 8000000,
            'is_active' => true,
            'tema_warna' => 'red',
            'setting' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id',
            ], JSON_THROW_ON_ERROR)
        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $perusahaan;
    }
}