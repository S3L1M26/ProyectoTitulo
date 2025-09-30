<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\AreaInteres;
use Illuminate\Support\Facades\Hash;

class AprendizTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario estudiante de prueba
        $student = User::updateOrCreate(
            ['email' => 'estudiante.test@example.com'],
            [
                'name' => 'Estudiante Test',
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil de aprendiz
        $aprendiz = Aprendiz::updateOrCreate(
            ['user_id' => $student->id],
            [
                'semestre' => 5,
                'objetivos' => 'Mi objetivo principal es aprender desarrollo web full-stack con Laravel y React. Quiero especializarme en el desarrollo de aplicaciones modernas y escalables, mejorar mis habilidades en bases de datos y obtener experiencia práctica en proyectos reales.'
            ]
        );

        // Asociar algunas áreas de interés
        $areasIds = AreaInteres::whereIn('nombre', ['Programación', 'Bases de Datos', 'Desarrollo Web'])
            ->pluck('id')
            ->toArray();
            
        if (!empty($areasIds)) {
            $aprendiz->areasInteres()->sync($areasIds);
        }

        // Crear un segundo usuario con perfil incompleto para testing
        $incompleteStudent = User::updateOrCreate(
            ['email' => 'estudiante.incompleto@example.com'],
            [
                'name' => 'Estudiante Incompleto',
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil parcialmente completo (solo semestre)
        Aprendiz::updateOrCreate(
            ['user_id' => $incompleteStudent->id],
            [
                'semestre' => 3,
                'objetivos' => '', // Sin objetivos
            ]
        );
        // Sin áreas de interés

        echo "Usuario estudiante completo creado: {$student->email}\n";
        echo "Usuario estudiante incompleto creado: {$incompleteStudent->email}\n";
        echo "Contraseña para ambos: password\n";
    }
}
