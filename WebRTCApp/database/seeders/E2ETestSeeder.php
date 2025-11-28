<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\SolicitudMentoria;
use App\Models\Mentoria;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class E2ETestSeeder extends Seeder
{
    /**
     * Seeder específico para tests E2E con Playwright
     * 
     * Crea usuarios y datos de prueba necesarios para:
     * - Flujo del mentor (aceptar solicitud, confirmar con Zoom)
     * - Flujo del estudiante (ver mentoría confirmada, unirse)
     */
    public function run(): void
    {
        // Limpiar datos anteriores de E2E
        $this->command->info('🧹 Limpiando datos E2E anteriores...');
        
        // Eliminar mentorías y solicitudes relacionadas con usuarios E2E
        $oldMentorUser = User::where('email', 'mentor@test.com')->first();
        $oldStudent1 = User::where('email', 'student@test.com')->first();
        $oldStudent2 = User::where('email', 'student2@test.com')->first();
        
        if ($oldMentorUser) {
            Mentoria::where('mentor_id', $oldMentorUser->id)->delete();
            SolicitudMentoria::where('mentor_id', $oldMentorUser->id)->orWhere('estudiante_id', $oldMentorUser->id)->delete();
            Mentor::where('user_id', $oldMentorUser->id)->delete();
        }
        
        if ($oldStudent1) {
            Mentoria::where('aprendiz_id', $oldStudent1->id)->delete();
            SolicitudMentoria::where('estudiante_id', $oldStudent1->id)->delete();
            Aprendiz::where('user_id', $oldStudent1->id)->delete();
        }
        
        if ($oldStudent2) {
            Mentoria::where('aprendiz_id', $oldStudent2->id)->delete();
            SolicitudMentoria::where('estudiante_id', $oldStudent2->id)->delete();
            Aprendiz::where('user_id', $oldStudent2->id)->delete();
        }
        
        // Eliminar usuarios
        User::whereIn('email', ['mentor@test.com', 'student@test.com', 'student2@test.com'])->delete();
        
        // 1. CREAR USUARIO MENTOR
        $mentorUser = User::create([
            'name' => 'Mentor E2E Test',
            'email' => 'mentor@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'mentor',
        ]);

        $mentor = Mentor::create([
            'user_id' => $mentorUser->id,
            'experiencia' => 'Senior Developer con 10 años de experiencia en Laravel, React y DevOps',
            'biografia' => 'Mentor especializado en desarrollo web moderno. He trabajado en startups y empresas tech liderando equipos de desarrollo.',
            'años_experiencia' => 10,
            'disponibilidad' => true,
            'disponibilidad_detalle' => 'Disponible lunes a viernes de 18:00 a 21:00',
            'disponible_ahora' => true,
            'cv_verified' => true,
        ]);

        // 2. CREAR USUARIO ESTUDIANTE
        $studentUser = User::create([
            'name' => 'Estudiante E2E Test',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'aprendiz',
        ]);

        $estudiante = Aprendiz::create([
            'user_id' => $studentUser->id,
            'semestre' => 6,
            'objetivos' => 'Aprender Laravel y arquitecturas modernas para conseguir mi primer trabajo como developer',
            'certificate_verified' => false,
        ]);

        // 3. CREAR USUARIO ESTUDIANTE ADICIONAL (para solicitud pendiente)
        $student2User = User::create([
            'name' => 'Estudiante Dos E2E Test',
            'email' => 'student2@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'aprendiz',
        ]);

        $estudiante2 = Aprendiz::create([
            'user_id' => $student2User->id,
            'semestre' => 4,
            'objetivos' => 'Aprender sobre CI/CD y despliegue en la nube',
            'certificate_verified' => false,
        ]);

        // 4. CREAR SOLICITUD PENDIENTE (para que el mentor la pueda aceptar)
        $solicitudPendiente = SolicitudMentoria::create([
            'estudiante_id' => $estudiante2->user_id,
            'mentor_id' => $mentor->user_id,
            'mensaje' => 'Hola, me gustaría aprender sobre arquitectura de microservicios y mejores prácticas en Laravel. ¿Podrías ayudarme?',
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
        ]);

        // 5. CREAR SOLICITUD ACEPTADA PERO NO CONFIRMADA
        $solicitudAceptada = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'mensaje' => 'Necesito ayuda con mi proyecto final de universidad en Laravel y React',
            'estado' => 'aceptada',
            'fecha_solicitud' => now()->subDays(2),
            'fecha_respuesta' => now()->subDays(1),
        ]);

        // 6. CREAR MENTORÍA CONFIRMADA CON ZOOM (para que el estudiante pueda unirse)
        $fechaMentoria = Carbon::now()->addDays(2);
        
        $mentoriaConfirmada = Mentoria::create([
            'solicitud_id' => $solicitudAceptada->id,
            'aprendiz_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'fecha' => $fechaMentoria->toDateString(),
            'hora' => '15:00',
            'duracion_minutos' => 60,
            'zoom_meeting_id' => '999888777',
            'enlace_reunion' => 'https://zoom.us/j/999888777?pwd=mockpassword',
            'zoom_password' => 'mockpass',
            'estado' => 'confirmada',
            'notas_mentor' => 'Prepara preguntas específicas sobre tu proyecto. Revisaremos arquitectura y mejores prácticas.',
        ]);

        // La solicitud permanece en estado 'aceptada' (no hay estado 'confirmada' en solicitudes)

        // 7. CREAR SOLICITUD RECHAZADA (para testing adicional)
        SolicitudMentoria::create([
            'estudiante_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'mensaje' => 'Otra solicitud de prueba',
            'estado' => 'rechazada',
            'fecha_solicitud' => now()->subDays(5),
            'fecha_respuesta' => now()->subDays(4),
        ]);

        // 8. CREAR MENTORÍA COMPLETADA (para historial)
        $solicitudCompletada = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'mensaje' => 'Solicitud completada para historial',
            'estado' => 'aceptada',
            'fecha_solicitud' => now()->subDays(10),
            'fecha_respuesta' => now()->subDays(9),
        ]);

        $fechaPasada = Carbon::now()->subDays(7);
        
        Mentoria::create([
            'solicitud_id' => $solicitudCompletada->id,
            'aprendiz_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'fecha' => $fechaPasada->toDateString(),
            'hora' => '10:00',
            'duracion_minutos' => 45,
            'zoom_meeting_id' => '111222333',
            'enlace_reunion' => 'https://zoom.us/j/111222333?pwd=old',
            'zoom_password' => 'oldpass',
            'estado' => 'completada',
            'notas_mentor' => 'Sesión completada exitosamente',
            'notas_aprendiz' => 'Excelente mentoría, aprendí mucho sobre Laravel',
        ]);

        // 9. CREAR MENTORÍA CANCELADA (para testing)
        $solicitudCancelada = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'mensaje' => 'Solicitud cancelada para testing',
            'estado' => 'cancelada',
            'fecha_solicitud' => now()->subDays(3),
            'fecha_respuesta' => now()->subDays(2),
        ]);

        Mentoria::create([
            'solicitud_id' => $solicitudCancelada->id,
            'aprendiz_id' => $estudiante->user_id,
            'mentor_id' => $mentor->user_id,
            'fecha' => Carbon::now()->addDays(5)->toDateString(),
            'hora' => '11:00',
            'duracion_minutos' => 30,
            'zoom_meeting_id' => '444555666',
            'enlace_reunion' => 'https://zoom.us/j/444555666?pwd=cancelled',
            'zoom_password' => 'cancelpass',
            'estado' => 'cancelada',
            'notas_mentor' => 'Surgió un imprevisto',
        ]);

        $this->command->info('✅ E2E Test Seeder ejecutado correctamente');
        $this->command->info('');
        $this->command->info('📧 Usuarios creados:');
        $this->command->info('   Mentor: mentor@test.com / password');
        $this->command->info('   Estudiante 1: student@test.com / password');
        $this->command->info('   Estudiante 2: student2@test.com / password');
        $this->command->info('');
        $this->command->info('📋 Datos creados:');
        $this->command->info("   - 1 solicitud pendiente (ID: {$solicitudPendiente->id})");
        $this->command->info("   - 1 solicitud aceptada (ID: {$solicitudAceptada->id})");
        $this->command->info("   - 1 mentoría confirmada (ID: {$mentoriaConfirmada->id})");
        $this->command->info("   - 1 mentoría completada");
        $this->command->info("   - 1 mentoría cancelada");
        $this->command->info('');
        $this->command->info('🎯 Mentoría confirmada para tests:');
        $this->command->info("   - Fecha: {$fechaMentoria->format('Y-m-d')} 15:00");
        $this->command->info("   - Zoom ID: {$mentoriaConfirmada->zoom_meeting_id}");
        $this->command->info("   - Join URL: {$mentoriaConfirmada->enlace_reunion}");
    }
}
