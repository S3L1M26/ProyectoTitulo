<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aprendiz;
use App\Models\VocationalSurvey;
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
        // Array de nombres de estudiantes demo (reducido a 5 para la demo en vivo)
        $students = [
            ['name' => 'Ana Torres', 'email' => 'ana.torres@demo.com'],
            ['name' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@demo.com'],
            ['name' => 'María González', 'email' => 'maria.gonzalez@demo.com'],
            ['name' => 'Diego López', 'email' => 'diego.lopez@demo.com'],
            ['name' => 'Sofía Martínez', 'email' => 'sofia.martinez@demo.com'],
        ];

        foreach ($students as $index => $studentData) {
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

            // Crear resultados de encuesta vocacional con datos variados para cada estudiante
            $surveyData = [
                // Ana Torres - Alta claridad
                ['clarity_interest' => 5, 'confidence_area' => 5, 'platform_usefulness' => 5, 'mentorship_usefulness' => 5, 'recent_change_reason' => 'La mentoría me ayudó a confirmar mi interés en desarrollo web', 'icv' => 0.95],
                // Carlos Ruiz - Claridad media-alta
                ['clarity_interest' => 4, 'confidence_area' => 4, 'platform_usefulness' => 5, 'mentorship_usefulness' => 4, 'recent_change_reason' => 'Ahora tengo más confianza en seguir backend', 'icv' => 0.78],
                // María González - Claridad media
                ['clarity_interest' => 3, 'confidence_area' => 3, 'platform_usefulness' => 4, 'mentorship_usefulness' => 4, 'recent_change_reason' => null, 'icv' => 0.65],
                // Diego López - Baja claridad
                ['clarity_interest' => 2, 'confidence_area' => 2, 'platform_usefulness' => 3, 'mentorship_usefulness' => 3, 'recent_change_reason' => 'Aún no tengo claro qué área elegir', 'icv' => 0.42],
                // Sofía Martínez - Sin encuesta (caso de estudiante que no ha llenado)
                null,
            ];

            // Crear encuesta solo si hay datos para este estudiante
            if ($surveyData[$index] !== null) {
                VocationalSurvey::updateOrCreate(
                    ['student_id' => $user->id],
                    $surveyData[$index]
                );
            }
        }

        $this->command->info('✓ Se crearon 5 estudiantes demo con perfiles vacíos y email verificado');
        $this->command->info('✓ Se crearon 4 resultados de encuesta vocacional con datos variados');
    }
}
