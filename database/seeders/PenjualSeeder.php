<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penjual;

class PenjualSeeder extends Seeder
{
    public function run(): void
    {

        // Buat 300 data penjual
        Penjual::factory()->count(300)->create();

        // Tambah beberapa data penjual tetap (opsional)
        $penjualTetap = [
            [
                'nama' => 'Budi Satriadi',
                'alamat' => 'Timpeh 7',
                'telepon' => '081345678901',
                'hutang' => 500000
            ],
            [
                'nama' => 'Dudung Gunawan',
                'alamat' => 'Sitiung 2',
                'telepon' => '081345678902',
                'hutang' => 2000000
            ],
            // Tambahkan data tetap lainnya jika diperlukan
        ];

        foreach ($penjualTetap as $penjual) {
            Penjual::create($penjual);
        }
    }
}