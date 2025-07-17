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
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => bcrypt('password'),
            'balance' => 1000.00,
        ]);

        \App\Models\User::create([
            'name' => 'María García',
            'email' => 'maria@example.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        \App\Models\User::create([
            'name' => 'Carlos López',
            'email' => 'carlos@example.com',
            'password' => bcrypt('password'),
            'balance' => 750.00,
        ]);
    }
}
