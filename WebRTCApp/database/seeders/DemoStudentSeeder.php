<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aprendiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates fresh demo student accounts with verified emails
     * and empty profiles (no mentorship requests or filled data).
     */
    public function run(): void
    {
        // Array de nombres de estudiantes demo
        $students = [
            ['name' => 'Ana Torres', 'email' => 'ana.torres@demo.com'],
            ['name' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@demo.com'],
            ['name' => 'María González', 'email' => 'maria.gonzalez@demo.com'],
            ['name' => 'Diego López', 'email' => 'diego.lopez@demo.com'],
            ['name' => 'Sofía Martínez', 'email' => 'sofia.martinez@demo.com'],
            ['name' => 'Lucas Fernández', 'email' => 'lucas.fernandez@demo.com'],
            ['name' => 'Valentina Díaz', 'email' => 'valentina.diaz@demo.com'],
            ['name' => 'Mateo Silva', 'email' => 'mateo.silva@demo.com'],
            ['name' => 'Isabella Rojas', 'email' => 'isabella.rojas@demo.com'],
            ['name' => 'Sebastián Castro', 'email' => 'sebastian.castro@demo.com'],
        ];

        foreach ($students as $studentData) {
            // Crear usuario con email verificado
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'), // Contraseña por defecto
                    'role' => 'student',
                    'email_verified_at' => now(), // Email verificado
                    'is_active' => true,
                ]
            );

            // Crear perfil de aprendiz vacío (sin campos llenados)
            Aprendiz::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'semestre' => null,
                    'objetivos' => null,
                    'certificate_verified' => false,
                ]
            );
        }

        $this->command->info('✓ Se crearon 10 estudiantes demo con perfiles vacíos y email verificado');
    }
}
