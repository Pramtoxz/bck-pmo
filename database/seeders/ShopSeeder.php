<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'jaya@motor.com'],
            [
                'name' => 'Toko Jaya Motor',
                'password' => bcrypt('password123'),
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'maju@motor.com'],
            [
                'name' => 'Toko Maju Motor',
                'password' => bcrypt('password123'),
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'sejahtera@motor.com'],
            [
                'name' => 'Toko Sejahtera Motor',
                'password' => bcrypt('password123'),
            ]
        );
    }
}
