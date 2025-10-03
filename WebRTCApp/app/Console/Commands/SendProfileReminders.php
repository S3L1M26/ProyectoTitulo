<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ProfileIncompleteReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendProfileReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:send-reminders {--test : Include recently created users for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to users with incomplete profiles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = User::whereIn('role', ['student', 'mentor']);
        
        // En modo test, incluir todos los usuarios
        if ($this->option('test')) {
            $this->info('🧪 Modo test activado - incluyendo usuarios recientes');
        } else {
            // En producción, solo usuarios de hace 7+ días
            $sevenDaysAgo = Carbon::now()->subDays(7);
            $query->where('created_at', '<=', $sevenDaysAgo);
            $this->info('📅 Buscando usuarios creados hace más de 7 días...');
        }
        
        $users = $query->with(['aprendiz.areasInteres', 'mentor'])->get();
        $this->info("👥 Encontrados {$users->count()} usuarios para verificar");

        $remindersSent = 0;

        foreach ($users as $user) {
            $profileData = $this->calculateProfileCompleteness($user);
            
            $this->line("📊 {$user->email} ({$user->role}): {$profileData['percentage']}% completo");
            
            // Enviar recordatorio si el perfil está < 80% completo
            if ($profileData['needs_reminder']) {
                $user->notify(new ProfileIncompleteReminder($profileData));
                $remindersSent++;
                
                $this->info("✉️  Recordatorio enviado a: {$user->email} ({$profileData['percentage']}% completo)");
                $this->line("   Campos faltantes: " . implode(', ', $profileData['missing_fields']));
            } else {
                $this->comment("✅ {$user->email} - Perfil completo o suficiente (≥80%)");
            }
        }

        $this->info("📤 Total de recordatorios enviados: {$remindersSent}");
        
        if ($remindersSent > 0) {
            $this->info("📧 Revisa MailHog en: http://localhost:8025");
        }
        
        return $remindersSent;
    }

    /**
     * Calcular completitud del perfil
     */
    private function calculateProfileCompleteness($user): array
    {
        if ($user->role === 'student') {
            return $this->calculateStudentCompleteness($user);
        } elseif ($user->role === 'mentor') {
            return $this->calculateMentorCompleteness($user);
        }

        return [
            'percentage' => 100,
            'missing_fields' => [],
            'needs_reminder' => false
        ];
    }

    private function calculateStudentCompleteness($user): array
    {
        $completedFields = 0;
        $totalFields = 3;
        $missingFields = [];

        // Verificar si existe el perfil de aprendiz
        if (!$user->aprendiz) {
            return [
                'percentage' => 0,
                'missing_fields' => ['Semestre', 'Áreas de interés', 'Objetivos personales'],
                'needs_reminder' => true
            ];
        }

        $aprendiz = $user->aprendiz;

        // Verificar semestre
        if ($aprendiz->semestre && $aprendiz->semestre > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Semestre';
        }

        // Verificar áreas de interés
        if ($aprendiz->areasInteres && $aprendiz->areasInteres->count() > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Áreas de interés';
        }

        // Verificar objetivos
        if ($aprendiz->objetivos && !empty(trim($aprendiz->objetivos))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Objetivos personales';
        }

        $percentage = round(($completedFields / $totalFields) * 100);

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'needs_reminder' => $percentage < 80
        ];
    }

    private function calculateMentorCompleteness($user): array
    {
        $completedFields = 0;
        $totalFields = 4;
        $missingFields = [];

        // Verificar si existe el perfil de mentor
        if (!$user->mentor) {
            return [
                'percentage' => 0,
                'missing_fields' => ['Experiencia profesional', 'Especialidades', 'Disponibilidad', 'Descripción del perfil'],
                'needs_reminder' => true
            ];
        }

        $mentor = $user->mentor;

        // Verificar experiencia
        if ($mentor->experiencia && !empty(trim($mentor->experiencia))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Experiencia profesional';
        }

        // Verificar especialidades
        if ($mentor->especialidades && !empty(trim($mentor->especialidades))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Especialidades';
        }

        // Verificar disponibilidad
        if ($mentor->disponibilidad && !empty(trim($mentor->disponibilidad))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Disponibilidad';
        }

        // Verificar descripción
        if ($mentor->descripcion && !empty(trim($mentor->descripcion))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Descripción del perfil';
        }

        $percentage = round(($completedFields / $totalFields) * 100);

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'needs_reminder' => $percentage < 80
        ];
    }
}