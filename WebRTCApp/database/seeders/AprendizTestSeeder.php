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

        // Asociar algunas áreas de interés (usando nombres correctos)
        $areasIds = AreaInteres::whereIn('nombre', [
            'Desarrollo Web Frontend', 
            'Desarrollo Web Backend', 
            'Análisis de Datos'
        ])->pluck('id')->toArray();
            
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

        // Crear usuarios mentores de prueba
        $mentor = User::updateOrCreate(
            ['email' => 'mentor.completo@example.com'],
            [
                'name' => 'Mentor Completo',
                'password' => Hash::make('password'),
                'role' => 'mentor',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil completo de mentor
        $mentorProfile = \App\Models\Mentor::updateOrCreate(
            ['user_id' => $mentor->id],
            [
                'experiencia' => 'Tengo más de 8 años de experiencia en desarrollo de software, especializado en aplicaciones web modernas y arquitecturas escalables. He trabajado en empresas tecnológicas líderes y he mentoreado a más de 20 desarrolladores junior.',
                'biografia' => 'Soy un desarrollador full-stack apasionado por la enseñanza y el crecimiento profesional. Mi objetivo es ayudar a los nuevos desarrolladores a acelerar su carrera profesional compartiendo conocimientos prácticos y mejores prácticas de la industria.',
                'años_experiencia' => 8,
                'disponibilidad' => 'Lunes a Viernes: 18:00-21:00, Sábados: 09:00-12:00',
                'disponibilidad_detalle' => 'Prefiero sesiones de 1-2 horas por videollamada. Flexible con horarios para estudiantes que trabajan. Disponible para consultas urgentes vía mensaje.',
                'calificacionPromedio' => 4.8,
            ]
        );

        // Asociar áreas de interés al mentor completo
        $mentorAreasIds = AreaInteres::whereIn('nombre', [
            'Desarrollo Web Frontend',
            'Desarrollo Web Backend',
            'DevOps'
        ])->pluck('id')->toArray();
        
        if (!empty($mentorAreasIds)) {
            $mentorProfile->areasInteres()->sync($mentorAreasIds);
        }

        $incompleteMentor = User::updateOrCreate(
            ['email' => 'mentor.incompleto@example.com'],
            [
                'name' => 'Mentor Incompleto',
                'password' => Hash::make('password'),
                'role' => 'mentor',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil parcialmente completo de mentor (solo algunos campos)
        \App\Models\Mentor::updateOrCreate(
            ['user_id' => $incompleteMentor->id],
            [
                'experiencia' => 'Desarrollador con experiencia en JavaScript.',
                'años_experiencia' => 3,
                // Sin biografia, disponibilidad, disponibilidad_detalle, ni áreas de interés
            ]
        );

        echo "Usuario estudiante completo creado: {$student->email}\n";
        echo "Usuario estudiante incompleto creado: {$incompleteStudent->email}\n";
        echo "Usuario mentor completo creado: {$mentor->email}\n";
        echo "Usuario mentor incompleto creado: {$incompleteMentor->email}\n";
        echo "Contraseña para todos: password\n";
    }
}
