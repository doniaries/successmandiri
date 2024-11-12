<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Penjual;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Super Admin
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Buat Kasir
        $kasir = User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Buat data perusahaan default
        $perusahaan = Perusahaan::create([
            'nama' => 'CV SUCCESS MANDIRI',
            'saldo' => 10000000,
            'logo_path' => null,
            'favicon_path' => null,
            'tema_warna' => 'amber',
            'alamat' => 'Dusun Sungai Moran Nagari Kamang',
            'email' => 'cv.success@example.com',
            'telepon' => '+62 823-8921-9670',
            'pimpinan' => 'Yondra',
            'kasir_id' => $kasir->id,
            'npwp' => '12.345.678.9-123.000',
            'setting' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id'
            ]),
            'is_active' => true,
        ]);

        $this->call([
            PenjualSeeder::class,

        ]);

        // Update kasir dengan perusahaan_id
        $kasir->update([
            'perusahaan_id' => $perusahaan->id
        ]);
    }
}
