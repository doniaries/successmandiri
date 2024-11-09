<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\User;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('email', 'kasir1@success-app.test')->first();

        Setting::create([
            'nama_perusahaan' => 'CV SUCCESS MANDIRI',
            'kode_perusahaan' => 'CSM',
            'tema_warna' => 'amber',
            'alamat' => 'Dusun Sungai Moran Nagari Kamang',
            'kabupaten' => 'Sijunjung',
            'provinsi' => 'Sumatera Barat',
            'kode_pos' => '26152',
            'telepon' => '+62 823-8921-9670',
            'email' => 'cv.success@example.com',
            'nama_pimpinan' => 'Yondra',
            'hp_pimpinan' => '+62 823-8921-9670',
            'kasir_id' => $kasir?->id,
            'npwp' => '12.345.678.9-123.000',
            'no_izin_usaha' => 'SIUP-123/456/789',
            'is_active' => true,
            'keterangan' => 'Perusahaan pengolahan hasil bumi',
            'pengaturan' => json_encode([
                'format_tanggal' => 'd/m/Y',
                'format_waktu' => 'H:i',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id'
            ])
        ]);
    }
}
