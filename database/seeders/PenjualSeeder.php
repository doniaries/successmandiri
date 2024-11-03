<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PenjualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $penjuals = [
            [
                'nama' => 'Budi',
                'alamat' => 'Jl. Supplier No. 1',
                'telepon' => '081345678901',
                'hutang' => 0,
            ],
            [
                'nama' => 'Dudung',
                'alamat' => 'Jl. Distributor No. 2',
                'telepon' => '081345678902',
                'hutang' => 0,
            ],
            [
                'nama' => 'wahyudi',
                'alamat' => 'Jl. Mitra No. 3',
                'telepon' => '081345678903',
                'hutang' => 0,
            ],
            [
                'nama' => 'Toni',
                'alamat' => 'Jl. Partner No. 4',
                'telepon' => '081345678904',
                'hutang' => 0,
            ],
            [
                'nama' => 'maman',
                'alamat' => 'Jl. Vendor No. 5',
                'telepon' => '081345678905',
                'hutang' => 0,
            ],
        ];

        foreach ($penjuals as $penjual) {
            \App\Models\Penjual::create($penjual);
        }
    }
}
