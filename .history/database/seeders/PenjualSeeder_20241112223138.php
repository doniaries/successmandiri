<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penjual;

class PenjualSeeder extends Seeder
{
    public function run(): void
    {
        $penjuals = [
            [
                'nama' => 'Budi Satriadi',
                'alamat' => 'Timpeh 7 ',
                'telepon' => '081345678901',
                'hutang' => 500000
            ],
            [
                'nama' => 'Dudung Gunawan',
                'alamat' => 'Jl. Distributor No. 2',
                'telepon' => '081345678902',
                'hutang' => 2000000
            ],
            [
                'nama' => 'Wahyudi Putra',
                'alamat' => 'Jl. Mitra No. 3',
                'telepon' => '081345678903',
                'hutang' => 10000000
            ],
        ];

        foreach ($penjuals as $penjual) {
            Penjual::create($penjual);
        }
    }
}
