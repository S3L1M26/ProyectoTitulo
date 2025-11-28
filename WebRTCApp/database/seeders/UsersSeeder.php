<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios solo si no existen (basado en email único)
        User::updateOrCreate(
            ['email' => 'mentor@gmail.com'],
            [
                'name' => 'Mentor',
                'password' => Hash::make('12345678'), 
                'role' => 'mentor',
            ]
        );

        User::updateOrCreate(
            ['email' => 'aprendiz@gmail.com'],
            [
                'name' => 'Aprendiz',
                'password' => Hash::make('12345678'), 
                'role' => 'student',
            ]
        );
    }
}
