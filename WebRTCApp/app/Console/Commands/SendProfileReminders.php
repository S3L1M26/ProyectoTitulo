<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ProfileIncompleteReminder;
use App\Jobs\SendProfileReminderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
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

        $reminderJobs = collect();
        $remindersSent = 0;

        foreach ($users as $user) {
            // Obtener datos de completitud usando el método del modelo
            $completenessData = $user->profile_completeness;
            $completenessPercentage = $completenessData['percentage'];
            
            $this->line("📊 {$user->email} ({$user->role}): {$completenessPercentage}% completo");
            
            // Preparar job si el perfil está < 80% completo
            if ($completenessPercentage < 80) {
                // OPTIMIZACIÓN: Crear job para envío asíncrono
                $reminderJobs->push(new SendProfileReminderJob($user, $completenessData));
                $remindersSent++;
                
                $this->info("🔄 Job preparado para: {$user->email} ({$completenessPercentage}% completo)");
            } else {
                $this->comment("✅ {$user->email} - Perfil completo o suficiente (≥80%)");
            }
        }

        // OPTIMIZACIÓN: Envío asíncrono en lote
        if ($reminderJobs->isNotEmpty()) {
            $this->info("📤 Enviando {$remindersSent} recordatorios de forma asíncrona...");
            
            Bus::batch($reminderJobs->toArray())
                ->name('Profile Reminders Batch')
                ->dispatch();
                
            $this->info("✅ Batch de recordatorios enviado a la cola exitosamente");
        }        $this->info("📤 Total de recordatorios programados: {$remindersSent}");
        
        if ($remindersSent > 0) {
            $this->info("📧 Los emails se enviarán de forma asíncrona");
            $this->info("🔍 Revisa MailHog en: http://localhost:8025");
            $this->info("📊 Monitorea la cola con: php artisan queue:work");
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
        $totalFields = 5; // experiencia, biografia, años_experiencia, disponibilidad, areasInteres
        $missingFields = [];

        // Verificar si existe el perfil de mentor
        if (!$user->mentor) {
            return [
                'percentage' => 0,
                'missing_fields' => ['Experiencia profesional', 'Biografía', 'Años de experiencia', 'Disponibilidad', 'Áreas de especialidad'],
                'needs_reminder' => true
            ];
        }

        $mentor = $user->mentor;
        $mentor->load('areasInteres'); // Cargar relación

        // Verificar experiencia
        if ($mentor->experiencia && !empty(trim($mentor->experiencia))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Experiencia profesional';
        }

        // Verificar biografía
        if ($mentor->biografia && !empty(trim($mentor->biografia))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Biografía';
        }

        // Verificar años de experiencia
        if ($mentor->años_experiencia && $mentor->años_experiencia > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Años de experiencia';
        }

        // Verificar disponibilidad
        if ($mentor->disponibilidad && !empty(trim($mentor->disponibilidad))) {
            $completedFields++;
        } else {
            $missingFields[] = 'Disponibilidad';
        }

        // Verificar áreas de especialidad (relación many-to-many)
        if ($mentor->areasInteres && $mentor->areasInteres->count() > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Áreas de especialidad';
        }

        $percentage = round(($completedFields / $totalFields) * 100);

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'needs_reminder' => $percentage < 80
        ];
    }
}