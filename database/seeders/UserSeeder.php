<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        // Create some sample users
        \App\Models\User::factory(3)->create([
            'status' => true,
            'email_verified_at' => now(),
        ]);
    }
}
