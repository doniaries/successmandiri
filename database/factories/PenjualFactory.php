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

        // Daftar lengkap daerah di Kecamatan Kamang Baru
        $daerah = [
            'Sungai Moran' => [
                'Jorong I Sungai Moran',
                'Jorong II Sungai Moran',
                'Jorong III Sungai Moran'
            ],
            'Kampung Surau' => [
                'Jorong I Kampung Surau',
                'Jorong II Kampung Surau',
                'Jorong III Kampung Surau'
            ],
            'Muaro Tais' => [
                'Jorong I Muaro Tais',
                'Jorong II Muaro Tais',
                'Jorong III Muaro Tais'
            ],
            'Lubuk Karak' => [
                'Jorong I Lubuk Karak',
                'Jorong II Lubuk Karak',
                'Jorong III Lubuk Karak'
            ],
            'Kamang' => [
                'Jorong I Kamang',
                'Jorong II Kamang',
                'Jorong III Kamang'
            ],
            'Gunung Medan' => [
                'Jorong I Gunung Medan',
                'Jorong II Gunung Medan',
                'Jorong III Gunung Medan'
            ]
        ];

        // Pilih nagari dan jorong secara random
        $nagari = array_rand($daerah);
        $jorong = $this->faker->randomElement($daerah[$nagari]);

        // Generate nama lengkap yang unik dengan menambahkan inisial
        $nama = $this->faker->randomElement($namaDepan) . ' ' .
            $this->faker->randomElement($namaBelakang) . ' ' .
            chr($this->faker->numberBetween(65, 90)); // Tambah inisial A-Z

        // Format alamat dengan detail
        $alamat = $jorong . ', Nagari ' . $nagari . ', Kec. Kamang Baru';

        return [
            'nama' => $nama,
            'alamat' => $alamat,
            'telepon' => '08' . $this->faker->numberBetween(1000000000, 9999999999),
            'hutang' => $this->faker->numberBetween(0, 20) * 100000, // Hutang 0-2jt dengan kelipatan 100rb
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}