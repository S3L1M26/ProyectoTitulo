<?php

namespace Database\Seeders;

use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class MentoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando mentorías de prueba...');

        // Obtener solicitudes aceptadas
        $solicitudesAceptadas = SolicitudMentoria::where('estado', 'aceptada')
            ->with(['estudiante', 'mentor'])
            ->get();

        if ($solicitudesAceptadas->isEmpty()) {
            $this->command->warn('⚠️  No hay solicitudes aceptadas. Ejecuta SolicitudMentoriaSeeder primero.');
            return;
        }

        $hoy = Carbon::now();
        $mentoriasCreadas = 0;

        foreach ($solicitudesAceptadas as $index => $solicitud) {
            try {
                // Crear mentorías en diferentes fechas
                $fechaBase = match ($index % 5) {
                    0 => $hoy->copy()->addDays(1),     // Mañana (para recordatorios)
                    1 => $hoy->copy()->addDays(2),     // Pasado mañana
                    2 => $hoy->copy()->addDays(7),     // En una semana
                    3 => $hoy->copy()->subDays(3),     // Hace 3 días (completada)
                    4 => $hoy->copy()->subDays(10),    // Hace 10 días (completada)
                    default => $hoy->copy()->addDays(rand(3, 14)),
                };

                // Horas variadas (entre 9:00 y 18:00)
                $hora = sprintf('%02d:00:00', rand(9, 18));

                // Determinar estado según la fecha
                $estado = 'confirmada';
                $recordatorioEnviado = false;
                
                if ($fechaBase->isPast()) {
                    $estado = 'completada';
                    $recordatorioEnviado = true;
                }

                // Datos de la mentoría
                $mentoriaData = [
                    'solicitud_id' => $solicitud->id,
                    'aprendiz_id' => $solicitud->estudiante_id,
                    'mentor_id' => $solicitud->mentor_id,
                    'fecha' => $fechaBase->toDateString(),
                    'hora' => $hora,
                    'duracion_minutos' => [60, 90, 120][rand(0, 2)], // 1h, 1.5h o 2h
                    'enlace_reunion' => 'https://zoom.us/j/' . rand(100000000, 999999999),
                    'zoom_meeting_id' => (string) rand(100000000, 999999999),
                    'zoom_password' => substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6),
                    'estado' => $estado,
                    'recordatorio_enviado' => $recordatorioEnviado,
                ];

                // Agregar notas si está completada
                if ($estado === 'completada') {
                    $mentoriaData['notas_mentor'] = 'Sesión productiva. El estudiante mostró interés y participación activa.';
                    $mentoriaData['notas_aprendiz'] = 'Excelente sesión, aprendí mucho sobre ' . ['Laravel', 'React', 'Bases de datos', 'APIs'][rand(0, 3)] . '.';
                }

                $mentoria = Mentoria::create($mentoriaData);
                $mentoriasCreadas++;

                $estadoIcon = match ($estado) {
                    'confirmada' => '📅',
                    'completada' => '✅',
                    default => '❓',
                };

                $this->command->info(
                    "{$estadoIcon} Mentoría creada: {$solicitud->estudiante->name} ↔️ {$solicitud->mentor->name} " .
                    "({$fechaBase->format('d/m/Y')} {$hora}) - {$estado}"
                );

            } catch (\Exception $e) {
                $this->command->error("❌ Error al crear mentoría para solicitud {$solicitud->id}: " . $e->getMessage());
                Log::error('Error en MentoriaSeeder', [
                    'solicitud_id' => $solicitud->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Resumen
        $this->command->newLine();
        $this->command->info("✅ Total de mentorías creadas: {$mentoriasCreadas}");
        $this->command->table(
            ['Estado', 'Cantidad'],
            [
                ['Confirmadas (futuras)', Mentoria::where('estado', 'confirmada')->whereDate('fecha', '>=', $hoy->toDateString())->count()],
                ['Completadas (pasadas)', Mentoria::where('estado', 'completada')->count()],
                ['Para recordatorio (mañana)', Mentoria::where('estado', 'confirmada')->whereDate('fecha', $hoy->copy()->addDay()->toDateString())->count()],
            ]
        );
    }
}
