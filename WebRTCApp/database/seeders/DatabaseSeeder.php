<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Seeders de datos base
            AreasInteresSeeder::class,
            
            // Seeders para demo en vivo con stakeholders
            // Base poblada con datos coherentes y variados
            DemoStudentSeeder::class,   // 5 estudiantes con perfiles vacíos + encuestas vocacionales
            DemoMentorSeeder::class,    // 8 mentores con perfiles completos y reseñas
            DemoMentoriasSeeder::class, // Solicitudes y mentorías con tasas de concreción variadas
            
            // Seeders de testing (deshabilitados para demo)
            // UsersSeeder::class,
            // AprendizTestSeeder::class,
            // SolicitudMentoriaSeeder::class,  // ← Reemplazado por DemoMentoriasSeeder
            // MentoriaSeeder::class,           // ← Reemplazado por DemoMentoriasSeeder
            // MentorReviewsSeeder::class,
        ]);
    }
}
