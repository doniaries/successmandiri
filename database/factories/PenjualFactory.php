<?php

namespace Database\Factories;

use App\Models\Penjual;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenjualFactory extends Factory
{
    protected $model = Penjual::class;

    public function definition()
    {
        // Daftar nama depan dan belakang untuk variasi
        $namaDepan = [
            'Budi',
            'Ahmad',
            'Dedi',
            'Eko',
            'Fadli',
            'Gunawan',
            'Hadi',
            'Irfan',
            'Joko',
            'Kurniawan',
            'Lutfi',
            'Muhammad',
            'Naufal',
            'Oscar',
            'Putra',
            'Qomar',
            'Rudi',
            'Surya',
            'Tono',
            'Umar',
            'Vino',
            'Wahyu',
            'Xaverius',
            'Yusuf',
            'Zaki'
        ];

        $namaBelakang = [
            'Santoso',
            'Wijaya',
            'Kusuma',
            'Pradana',
            'Pratama',
            'Putra',
            'Saputra',
            'Firmansyah',
            'Hidayat',
            'Nugroho',
            'Ramadhan',
            'Sugiarto',
            'Setiawan',
            'Pribadi',
            'Wicaksono',
            'Yuliyanto',
            'Utama',
            'Suryadi',
            'Permana',
            'Nugraha'
        ];

        // Daftar nama daerah untuk alamat
        $daerah = [
            'Timpeh',
            'Dharmasraya',
            'Sitiung',
            'Pulau Punjung',
            'Koto Baru',
            'Asam Jujuhan',
            'Koto Salak',
            'Padang Laweh',
            'Tiumang',
            'Sungai Rumbai',
            'Ranah Batahan',
            'Koto Besar',
            'Sungai Dareh'
        ];

        // Generate nama lengkap random
        $nama = $this->faker->randomElement($namaDepan) . ' ' .
            $this->faker->randomElement($namaBelakang);

        // Generate alamat dengan format dan daerah yang relevan
        $alamat = 'Jorong ' . $this->faker->numberBetween(1, 10) . ' ' .
            $this->faker->randomElement($daerah);

        return [
            'nama' => $nama,
            'alamat' => $alamat,
            // Format nomor telepon Indonesia
            'telepon' => '08' . $this->faker->numberBetween(1000000000, 9999999999),
            // Generate hutang random dengan kelipatan 50000
            'hutang' => $this->faker->numberBetween(0, 100) * 50000,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
