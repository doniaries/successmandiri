<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Kasir 3',
            'email' => 'kasir3@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Kasir 4',
            'email' => 'kasir4@gmail.com',
            'password' => Hash::make('password'),
            'status' => true,
            'email_verified_at' => now(),
        ]);
    }
}
