<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some random user IDs for kasir
        $kasirIds = \App\Models\User::where('status', true)
            ->pluck('id')
            ->toArray();

        $perusahaans = [
            [
                'nama' => 'CV SUCCESS MANDIRI',
                'alamat' => 'Kamang',
                'pimpinan' => 'Yondra',
                'kasir_id' => $kasirIds[array_rand($kasirIds)],
                'is_active' => true,
            ],
            [
                'nama' => 'UD SUCCESS',
                'alamat' => 'Kamang',
                'pimpinan' => 'Yondra',
                'kasir_id' => $kasirIds[array_rand($kasirIds)],
                'is_active' => true,
            ],
            [
                'nama' => 'KOPERASI SUCCESS',
                'alamat' => 'Kamang',
                'pimpinan' => 'Yondra',
                'kasir_id' => $kasirIds[array_rand($kasirIds)],
                'is_active' => true,
            ],
        ];

        foreach ($perusahaans as $perusahaan) {
            \App\Models\Perusahaan::create($perusahaan);
        }
    }
}
