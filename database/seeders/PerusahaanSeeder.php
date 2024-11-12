<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use phpDocumentor\Reflection\Types\Null_;

class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some random user IDs for kasir
        $kasirIds = User::where('status', true)
            ->pluck('id')
            ->toArray();

        $perusahaans = [
            [
                'nama' => 'CV SUCCESS MANDIRI',
                'saldo' => 10000000,
                'logo_path' => null,
                'favicon_path' => null,
                'tema_warna' => 'amber',
                'alamat' => 'Dusun Sungai Moran Nagari Kamang',
                'telepon' => '+62 823-8921-9670',
                'pimpinan' => 'Yondra',
                'kasir_id' => null,
                'npwp' => '12.345.678.9-123.000',
                'is_active' => true,
                'setting' => json_encode([
                    'format_tanggal' => 'd/m/Y',
                    'format_waktu' => 'H:i',
                    'zona_waktu' => 'Asia/Jakarta',
                    'bahasa' => 'id',
                ]),
            ],

        ];

        foreach ($perusahaans as $perusahaan) {
            Perusahaan::create($perusahaan);
        }
    }
}
