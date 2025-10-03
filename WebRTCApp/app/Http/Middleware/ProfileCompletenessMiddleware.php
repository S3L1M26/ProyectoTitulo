<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProfileCompletenessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ejecutar la lógica después de la respuesta para evitar timeouts
        $response = $next($request);
        
        $user = Auth::user();
        
        if ($user && in_array($user->role, ['student', 'mentor'])) {
            try {
                $profileCompleteness = $this->calculateProfileCompleteness($user);
                
                // Agregar a la sesión para uso posterior
                session(['profile_completeness' => $profileCompleteness]);
            } catch (\Exception $e) {
                // Si hay error, simplemente continuar sin bloquear la respuesta
                logger()->error('Error calculating profile completeness: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Calcular el porcentaje de completitud del perfil para cualquier rol
     */
    private function calculateProfileCompleteness($user): array
    {
        if ($user->role === 'student') {
            return $this->calculateStudentCompleteness($user);
        } elseif ($user->role === 'mentor') {
            return $this->calculateMentorCompleteness($user);
        }

        // Para otros roles, consideramos completo
        return [
            'percentage' => 100,
            'missing_fields' => [],
            'is_incomplete' => false,
            'needs_reminder' => false
        ];
    }

    /**
     * Calcular completitud para estudiantes
     */
    private function calculateStudentCompleteness($user): array
    {
        $completedFields = 0;
        $totalFields = 3; // semestre, areas_interes, objetivos
        $missingFields = [];

        // Cargar relación solo si es necesario
        if (!$user->relationLoaded('aprendiz')) {
            $user->load('aprendiz.areasInteres');
        }

        $aprendiz = $user->aprendiz;

        // Verificar semestre
        if ($aprendiz && $aprendiz->semestre && $aprendiz->semestre > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Semestre';
        }

        // Verificar áreas de interés
        if ($aprendiz && $aprendiz->areasInteres && $aprendiz->areasInteres->count() > 0) {
            $completedFields++;
        } else {
            $missingFields[] = 'Áreas de interés';
        }

        // Verificar objetivos
        if ($aprendiz && $aprendiz->objetivos && trim($aprendiz->objetivos) !== '') {
            $completedFields++;
        } else {
            $missingFields[] = 'Objetivos personales';
        }

        $percentage = round(($completedFields / $totalFields) * 100);

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'is_incomplete' => $percentage < 100,
            'needs_reminder' => $percentage < 80
        ];
    }

    /**
     * Calcular completitud para mentores
     */
    private function calculateMentorCompleteness($user): array
    {
        $completedFields = 0;
        $totalFields = 4; // experiencia, especialidades, disponibilidad, descripcion
        $missingFields = [];

        // Cargar relación solo si es necesario
        if (!$user->relationLoaded('mentor')) {
            $user->load('mentor');
        }

        $mentor = $user->mentor;

        // Verificar experiencia
        if ($mentor && $mentor->experiencia && trim($mentor->experiencia) !== '') {
            $completedFields++;
        } else {
            $missingFields[] = 'Experiencia profesional';
        }

        // Verificar especialidades
        if ($mentor && $mentor->especialidades && trim($mentor->especialidades) !== '') {
            $completedFields++;
        } else {
            $missingFields[] = 'Especialidades';
        }

        // Verificar disponibilidad
        if ($mentor && $mentor->disponibilidad && trim($mentor->disponibilidad) !== '') {
            $completedFields++;
        } else {
            $missingFields[] = 'Disponibilidad';
        }

        // Verificar descripción del perfil
        if ($mentor && $mentor->descripcion && trim($mentor->descripcion) !== '') {
            $completedFields++;
        } else {
            $missingFields[] = 'Descripción del perfil';
        }

        $percentage = round(($completedFields / $totalFields) * 100);

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'is_incomplete' => $percentage < 100,
            'needs_reminder' => $percentage < 80
        ];
    }
}
