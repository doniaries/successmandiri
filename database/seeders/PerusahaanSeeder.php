<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
                'kode_perusahaan' => 'CSM',
                'alamat' => 'Dusun Sungai Moran Nagari Kamang',
                'kabupaten' => 'Sijunjung',
                'provinsi' => 'Sumatera Barat',
                'kode_pos' => '26152',
                'telepon' => '+62 823-8921-9670',
                'email' => 'cv.success@example.com',
                'website' => 'www.cvsuccess.com',
                'pimpinan' => 'Yondra',
                'npwp' => '12.345.678.9-123.000',
                'no_izin_usaha' => 'SIUP-123/456/789',
                'saldo' => 0,
                'is_active' => true,
                'keterangan' => 'Perusahaan pengolahan hasil bumi',
                'tema_warna' => 'amber',
                'setting' => json_encode([
                    'format_tanggal' => 'd/m/Y',
                    'format_waktu' => 'H:i',
                    'zona_waktu' => 'Asia/Jakarta',
                    'bahasa' => 'id',
                ]),
            ],
            [
                'nama' => 'UD SUCCESS',
                'kode_perusahaan' => 'UDS',
                'alamat' => 'Dusun Sungai Moran Nagari Kamang',
                'kabupaten' => 'Sijunjung',
                'provinsi' => 'Sumatera Barat',
                'kode_pos' => '26152',
                'telepon' => '+62 823-8921-9670',
                'email' => 'ud.success@example.com',
                'website' => 'www.udsuccess.com',
                'pimpinan' => 'Yondra',
                'npwp' => '98.765.432.1-123.000',
                'no_izin_usaha' => 'SIUP-987/654/321',
                'saldo' => 0,
                'is_active' => true,
                'keterangan' => 'Unit dagang hasil bumi',
                'tema_warna' => 'blue',
                'setting' => json_encode([
                    'format_tanggal' => 'd/m/Y',
                    'format_waktu' => 'H:i',
                    'zona_waktu' => 'Asia/Jakarta',
                    'bahasa' => 'id',
                ]),
            ],
            [
                'nama' => 'KOPERASI SUCCESS',
                'kode_perusahaan' => 'KPS',
                'alamat' => 'Dusun Sungai Moran Nagari Kamang',
                'kabupaten' => 'Sijunjung',
                'provinsi' => 'Sumatera Barat',
                'kode_pos' => '26152',
                'telepon' => '+62 823-8921-9670',
                'email' => 'koperasi.success@example.com',
                'website' => 'www.koperasisuccess.com',
                'pimpinan' => 'Yondra',
                'npwp' => '13.579.246.8-123.000',
                'no_izin_usaha' => 'NIK-135/792/468',
                'saldo' => 0,
                'is_active' => true,
                'keterangan' => 'Koperasi simpan pinjam dan hasil bumi',
                'tema_warna' => 'green',
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
