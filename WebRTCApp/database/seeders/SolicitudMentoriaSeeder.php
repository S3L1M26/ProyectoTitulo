<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\Models\SolicitudMentoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SolicitudMentoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener estudiantes y mentores existentes
        $estudiantes = User::where('role', 'aprendiz')->get();
        $mentores = User::where('role', 'mentor')->get();

        if ($estudiantes->isEmpty() || $mentores->isEmpty()) {
            $this->command->warn('No hay suficientes usuarios para crear solicitudes. Ejecuta UserSeeder primero.');
            return;
        }

        // Crear solicitudes de prueba
        $this->command->info('Creando solicitudes de mentoría...');

        foreach ($estudiantes->take(5) as $estudiante) {
            // Verificar que el estudiante tenga perfil de aprendiz
            $aprendiz = Aprendiz::where('user_id', $estudiante->id)->first();
            if (!$aprendiz) {
                continue;
            }

            // Seleccionar 2-3 mentores aleatorios
            $mentoresSeleccionados = $mentores->random(rand(2, min(3, $mentores->count())));

            foreach ($mentoresSeleccionados as $index => $mentor) {
                // Verificar que el mentor tenga perfil
                $mentorProfile = Mentor::where('user_id', $mentor->id)->first();
                if (!$mentorProfile) {
                    continue;
                }

                // Crear solicitudes con diferentes estados
                if ($index === 0) {
                    // Primera solicitud: pendiente
                    SolicitudMentoria::factory()->pendiente()->create([
                        'estudiante_id' => $estudiante->id,
                        'mentor_id' => $mentor->id,
                    ]);
                    $this->command->info("✓ Solicitud pendiente creada: {$estudiante->name} -> {$mentor->name}");
                } elseif ($index === 1) {
                    // Segunda solicitud: aceptada
                    SolicitudMentoria::factory()->aceptada()->create([
                        'estudiante_id' => $estudiante->id,
                        'mentor_id' => $mentor->id,
                    ]);
                    $this->command->info("✓ Solicitud aceptada creada: {$estudiante->name} -> {$mentor->name}");
                } else {
                    // Tercera solicitud: rechazada
                    SolicitudMentoria::factory()->rechazada()->create([
                        'estudiante_id' => $estudiante->id,
                        'mentor_id' => $mentor->id,
                    ]);
                    $this->command->info("✓ Solicitud rechazada creada: {$estudiante->name} -> {$mentor->name}");
                }
            }
        }

        $total = SolicitudMentoria::count();
        $this->command->info("\n✅ Total de solicitudes creadas: {$total}");
        $this->command->info("  - Pendientes: " . SolicitudMentoria::where('estado', 'pendiente')->count());
        $this->command->info("  - Aceptadas: " . SolicitudMentoria::where('estado', 'aceptada')->count());
        $this->command->info("  - Rechazadas: " . SolicitudMentoria::where('estado', 'rechazada')->count());
    }
}
