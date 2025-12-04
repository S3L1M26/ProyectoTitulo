<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SolicitudMentoria;
use App\Models\Mentoria;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoMentoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea solicitudes y mentorías para usuarios demo:
     * - 5 estudiantes demo
     * - 8 mentores demo
     * - Datos variados para mostrar diferentes estados y tasas de concreción
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando creación de solicitudes y mentorías demo...');

        // Obtener usuarios demo
        $estudiantes = User::where('role', 'student')
            ->where('email', 'LIKE', '%@demo.com')
            ->get();

        $mentores = User::where('role', 'mentor')
            ->where('email', 'LIKE', '%@demo.com')
            ->get();

        if ($estudiantes->count() !== 5) {
            $this->command->warn("⚠️  Se esperaban 5 estudiantes demo, se encontraron {$estudiantes->count()}");
        }

        if ($mentores->count() !== 8) {
            $this->command->warn("⚠️  Se esperaban 8 mentores demo, se encontraron {$mentores->count()}");
        }

        if ($estudiantes->isEmpty() || $mentores->isEmpty()) {
            $this->command->error('❌ No hay suficientes usuarios demo. Ejecuta DemoStudentSeeder y DemoMentorSeeder primero.');
            return;
        }

        // Configuración de solicitudes por mentor para mostrar diferentes tasas de concreción
        // Formato: email_mentor => ['aceptadas' => x, 'pendientes' => y, 'rechazadas' => z]
        $solicitudesPorMentor = [
            'pedro.ramirez@demo.com' => ['aceptadas' => 4, 'pendientes' => 1, 'rechazadas' => 1],
            'laura.jimenez@demo.com' => ['aceptadas' => 3, 'pendientes' => 2, 'rechazadas' => 0],
            'jorge.salinas@demo.com' => ['aceptadas' => 3, 'pendientes' => 1, 'rechazadas' => 1],
            'sofia.lopez@demo.com' => ['aceptadas' => 2, 'pendientes' => 1, 'rechazadas' => 0],
            'ricardo.moreno@demo.com' => ['aceptadas' => 2, 'pendientes' => 0, 'rechazadas' => 1],
            'camila.ruiz@demo.com' => ['aceptadas' => 4, 'pendientes' => 2, 'rechazadas' => 0],
            'diego.fernandez@demo.com' => ['aceptadas' => 1, 'pendientes' => 0, 'rechazadas' => 2],
            'valeria.herrera@demo.com' => ['aceptadas' => 3, 'pendientes' => 1, 'rechazadas' => 1],
        ];

        $hoy = Carbon::now();
        $totalSolicitudes = 0;
        $totalMentorias = 0;

        foreach ($solicitudesPorMentor as $mentorEmail => $estados) {
            $mentor = $mentores->firstWhere('email', $mentorEmail);
            
            if (!$mentor) {
                $this->command->warn("⚠️  Mentor no encontrado: {$mentorEmail}");
                continue;
            }

            $totalEstados = array_sum($estados);
            $estudiantesRotados = $estudiantes->shuffle(); // Rotar estudiantes para variedad

            $indexEstudiante = 0;

            foreach (['aceptadas', 'pendientes', 'rechazadas'] as $estadoKey) {
                $cantidad = $estados[$estadoKey];
                
                for ($i = 0; $i < $cantidad; $i++) {
                    // Rotar entre los 5 estudiantes
                    $estudiante = $estudiantesRotados[$indexEstudiante % $estudiantesRotados->count()];
                    $indexEstudiante++;

                    // Crear solicitud
                    $fechaSolicitud = $hoy->copy()->subDays(rand(5, 20));
                    
                    $solicitud = SolicitudMentoria::create([
                        'estudiante_id' => $estudiante->id,
                        'mentor_id' => $mentor->id,
                        'mensaje' => $this->generarMensajeSolicitud($estadoKey, $i),
                        'estado' => $this->mapearEstado($estadoKey),
                        'fecha_solicitud' => $fechaSolicitud,
                        'fecha_respuesta' => ($estadoKey !== 'pendientes') ? $fechaSolicitud->copy()->addDays(rand(1, 3)) : null,
                    ]);

                    $totalSolicitudes++;

                    // Si es aceptada, crear mentoría
                    if ($estadoKey === 'aceptadas') {
                        $this->crearMentoria($solicitud, $hoy, $i);
                        $totalMentorias++;
                    }
                }
            }

            $aceptadasCount = $estados['aceptadas'];
            $totalCount = array_sum($estados);
            $tasa = $totalCount > 0 ? round(($aceptadasCount / $totalCount) * 100, 1) : 0;
            
            $this->command->info("✓ {$mentor->name}: {$aceptadasCount}/{$totalCount} aceptadas ({$tasa}%)");
        }

        // Resumen
        $this->command->newLine();
        $this->command->info("✅ Total de solicitudes creadas: {$totalSolicitudes}");
        $this->command->info("  - Pendientes: " . SolicitudMentoria::where('estado', 'pendiente')->count());
        $this->command->info("  - Aceptadas: " . SolicitudMentoria::where('estado', 'aceptada')->count());
        $this->command->info("  - Rechazadas: " . SolicitudMentoria::where('estado', 'rechazada')->count());
        
        $this->command->newLine();
        $this->command->info("✅ Total de mentorías creadas: {$totalMentorias}");
        $this->command->info("  - Confirmadas (futuras): " . Mentoria::where('estado', 'confirmada')->whereDate('fecha', '>=', $hoy->toDateString())->count());
        $this->command->info("  - Completadas (pasadas): " . Mentoria::where('estado', 'completada')->count());
        $this->command->info("  - Para recordatorio (próximas 48h): " . Mentoria::where('estado', 'confirmada')
            ->whereDate('fecha', '>=', $hoy->toDateString())
            ->whereDate('fecha', '<=', $hoy->copy()->addDays(2)->toDateString())
            ->count());
    }

    /**
     * Generar mensaje de solicitud variado
     */
    private function generarMensajeSolicitud(string $estadoKey, int $index): string
    {
        $mensajes = [
            'aceptadas' => [
                'Hola, me interesa mucho tu perfil y experiencia. Me gustaría conversar sobre orientación profesional en esta área.',
                'Buenos días, estoy explorando opciones de carrera y tu experiencia me parece muy relevante. ¿Podríamos agendar una mentoría?',
                'Hola, leí tu perfil y me gustaría conocer más sobre tu experiencia en el campo. ¿Estarías disponible para una sesión?',
                'Me gustaría recibir orientación sobre las oportunidades laborales en esta área. Tu experiencia sería muy valiosa.',
            ],
            'pendientes' => [
                'Hola, estoy en proceso de definir mi orientación vocacional y me gustaría conocer tu perspectiva.',
                'Buenos días, me interesa mucho esta área y me gustaría conversar contigo sobre las posibilidades.',
            ],
            'rechazadas' => [
                'Hola, quisiera una mentoría urgente para mañana si es posible.',
                'Me interesa esta área pero no estoy seguro si es para mí.',
            ],
        ];

        $lista = $mensajes[$estadoKey];
        return $lista[$index % count($lista)];
    }

    /**
     * Mapear estado key a valor de BD
     */
    private function mapearEstado(string $estadoKey): string
    {
        return match($estadoKey) {
            'aceptadas' => 'aceptada',
            'pendientes' => 'pendiente',
            'rechazadas' => 'rechazada',
            default => 'pendiente',
        };
    }

    /**
     * Crear mentoría asociada a solicitud aceptada
     */
    private function crearMentoria(SolicitudMentoria $solicitud, Carbon $hoy, int $index): void
    {
        // Distribuir mentorías en diferentes fechas para mostrar variedad
        // 40% futuras, 60% completadas
        $esFutura = $index % 5 < 2; // 2 de cada 5 son futuras

        if ($esFutura) {
            // Mentorías futuras (próximos 1-14 días)
            $fechaBase = $hoy->copy()->addDays(rand(1, 14));
            $estado = 'confirmada';
            $recordatorioEnviado = false;
            $notasMentor = null;
            $notasAprendiz = null;
        } else {
            // Mentorías completadas (últimos 3-30 días)
            $fechaBase = $hoy->copy()->subDays(rand(3, 30));
            $estado = 'completada';
            $recordatorioEnviado = true;
            $notasMentor = 'Sesión productiva. El estudiante mostró interés y participación activa en la conversación sobre orientación vocacional.';
            $notasAprendiz = $this->generarNotasAprendiz();
        }

        // Horas variadas (entre 9:00 y 18:00)
        $hora = sprintf('%02d:00:00', rand(9, 18));

        Mentoria::create([
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
            'notas_mentor' => $notasMentor,
            'notas_aprendiz' => $notasAprendiz,
        ]);
    }

    /**
     * Generar notas de aprendiz variadas
     */
    private function generarNotasAprendiz(): string
    {
        $notas = [
            'Excelente sesión, aprendí mucho sobre las oportunidades reales en el área.',
            'Muy útil la conversación, ahora tengo más claridad sobre mi orientación vocacional.',
            'El mentor fue muy claro al explicar las diferencias entre áreas, me ayudó mucho.',
            'Sesión productiva, me dio perspectivas que no había considerado antes.',
            'Muy valiosa la experiencia compartida, me siento más confiado en mi decisión.',
        ];

        return $notas[array_rand($notas)];
    }
}
