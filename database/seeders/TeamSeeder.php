<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Pekerja;
use App\Models\Penjual;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Super Admin yang bisa akses semua team
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Buat Team 1
        $team1 = Team::create([
            'name' => 'Success Mandiri',
            'slug' => 'success-mandiri',
            'saldo' => 0,
            'alamat' => 'Jl. Raya Success Mandiri No. 1',
            'telepon' => '08123456789',
            'email' => 'success@mandiri.com',
            'pimpinan' => 'Pimpinan Success Mandiri',
            'npwp' => '12.345.678.9-012.345',
            'is_active' => true
        ]);

        // Buat Team 2
        $team2 = Team::create([
            'name' => 'Maju Bersama',
            'slug' => 'maju-bersama',
            'saldo' => 0,
            'alamat' => 'Jl. Raya Maju Bersama No. 2',
            'telepon' => '08987654321',
            'email' => 'maju@bersama.com',
            'pimpinan' => 'Pimpinan Maju Bersama',
            'npwp' => '98.765.432.1-098.765',
            'is_active' => true
        ]);

        // Buat Admin untuk Team 1
        $adminTeam1 = User::create([
            'name' => 'Admin Success Mandiri',
            'email' => 'admin.success@mandiri.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Buat Admin untuk Team 2
        $adminTeam2 = User::create([
            'name' => 'Admin Maju Bersama',
            'email' => 'admin.maju@bersama.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Hubungkan Admin dengan Team masing-masing menggunakan relasi
        $team1->users()->attach($adminTeam1->id);
        $team2->users()->attach($adminTeam2->id);

        // Hubungkan Super Admin dengan kedua Team menggunakan relasi
        $superadmin->teams()->attach([$team1->id, $team2->id]);

        // Buat Penjual untuk Team 1
        $penjualTeam1 = [
            [
                'nama' => 'Penjual 1 Success',
                'alamat' => 'Alamat Penjual 1 Success',
                'telepon' => '08111111111',
                'hutang' => 0,
                'team_id' => $team1->id,
            ],
            [
                'nama' => 'Penjual 2 Success',
                'alamat' => 'Alamat Penjual 2 Success',
                'telepon' => '08222222222',
                'hutang' => 0,
                'team_id' => $team1->id,
            ],
        ];

        // Buat Penjual untuk Team 2
        $penjualTeam2 = [
            [
                'nama' => 'Penjual 1 Maju',
                'alamat' => 'Alamat Penjual 1 Maju',
                'telepon' => '08333333333',
                'hutang' => 0,
                'team_id' => $team2->id,
            ],
            [
                'nama' => 'Penjual 2 Maju',
                'alamat' => 'Alamat Penjual 2 Maju',
                'telepon' => '08444444444',
                'hutang' => 0,
                'team_id' => $team2->id,
            ],
        ];

        // Insert Penjual
        foreach ($penjualTeam1 as $penjual) {
            Penjual::create($penjual);
        }

        foreach ($penjualTeam2 as $penjual) {
            Penjual::create($penjual);
        }

        // Buat Pekerja untuk Team 1
        $pekerjaTeam1 = [
            [
                'nama' => 'Pekerja 1 Success',
                'alamat' => 'Alamat Pekerja 1 Success',
                'telepon' => '08555555555',
                'pendapatan' => '0',
                'hutang' => '0',
                'team_id' => $team1->id,
            ],
            [
                'nama' => 'Pekerja 2 Success',
                'alamat' => 'Alamat Pekerja 2 Success',
                'telepon' => '08666666666',
                'pendapatan' => '0',
                'hutang' => '0',
                'team_id' => $team1->id,
            ],
        ];

        // Buat Pekerja untuk Team 2
        $pekerjaTeam2 = [
            [
                'nama' => 'Pekerja 1 Maju',
                'alamat' => 'Alamat Pekerja 1 Maju',
                'telepon' => '08777777777',
                'pendapatan' => '0',
                'hutang' => '0',
                'team_id' => $team2->id,
            ],
            [
                'nama' => 'Pekerja 2 Maju',
                'alamat' => 'Alamat Pekerja 2 Maju',
                'telepon' => '08888888888',
                'pendapatan' => '0',
                'hutang' => '0',
                'team_id' => $team2->id,
            ],
        ];

        // Insert Pekerja
        foreach ($pekerjaTeam1 as $pekerja) {
            Pekerja::create($pekerja);
        }

        foreach ($pekerjaTeam2 as $pekerja) {
            Pekerja::create($pekerja);
        }
    }
}
