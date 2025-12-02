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
            
            // Seeders para demo con stakeholders (base poblada sin solicitudes/mentorías)
            DemoStudentSeeder::class,  // 10 estudiantes demo con perfiles vacíos
            DemoMentorSeeder::class,   // 8 mentores demo con perfiles completos y reseñas
            
            // Seeders de testing (deshabilitados para demo)
            //UsersSeeder::class,
            //AprendizTestSeeder::class,
            //SolicitudMentoriaSeeder::class,
            //MentoriaSeeder::class,
            //MentorReviewsSeeder::class,
        ]);
    }
}
