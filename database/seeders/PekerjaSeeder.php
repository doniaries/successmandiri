<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PekerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pekerjas = [
            [
                'nama' => 'Ahmad Santoso',
                'alamat' => 'Jl. Pekerja No. 1',
                'telepon' => '081234567890',
                'pendapatan' => 0,
                'hutang' => 1000000,
            ],
            [
                'nama' => 'Budi Setiawan',
                'alamat' => 'Jl. Pekerja No. 2',
                'telepon' => '081234567891',
                'pendapatan' => 0,
                'hutang' => 2000000,
            ],
            [
                'nama' => 'Candra Wijaya',
                'alamat' => 'Jl. Pekerja No. 3',
                'telepon' => '081234567892',
                'pendapatan' => 0,
                'hutang' => 0,
            ],
            [
                'nama' => 'Dedi Kurniawan',
                'alamat' => 'Jl. Pekerja No. 4',
                'telepon' => '081234567893',
                'pendapatan' => 0,
                'hutang' => 2000000,
            ],
            [
                'nama' => 'Eko Prasetyo',
                'alamat' => 'Jl. Pekerja No. 5',
                'telepon' => '081234567894',
                'pendapatan' => 0,
                'hutang' => 500000,
            ],
        ];

        foreach ($pekerjas as $pekerja) {
            \App\Models\Pekerja::create($pekerja);
        }
    }
}