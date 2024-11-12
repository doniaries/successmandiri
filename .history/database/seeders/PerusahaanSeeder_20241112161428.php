<?php

namespace Database\Seeders;

use App\Models\Perusahaan;
use Illuminate\Database\Seeder;

class PerusahaanSeeder extends Seeder
{
    public function run()
    {
        if (!Perusahaan::exists()) {
            Perusahaan::create([
                'nama' => 'CV SUCCESS MANDIRI',
                'saldo' => 10000000,
                'tema_warna' => 'amber',
                'alamat' => 'Dusun Sungai Moran Nagari Kamang',
                'telepon' => '+62 823-8921-9670',
                'email' => 'cv.success@example.com',
                'pimpinan' => 'Yondra',
                'kasir_id' => $kasir->id,
                'npwp' => '12.345.678.9-123.000',
                'is_active' => true,
                'setting' => json_encode([
                    'format_tanggal' => 'd/m/Y',
                    'format_waktu' => 'H:i',
                    'zona_waktu' => 'Asia/Jakarta',
                    'bahasa' => 'id'
                ])
            ]);
        }
    }
}
