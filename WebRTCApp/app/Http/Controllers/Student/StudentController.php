<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        return Inertia::render('Student/Dashboard/Index', [
            'mentorSuggestions' => $this->getMentorSuggestions(),
        ]);
    }

    /**
     * Get mentor suggestions for the authenticated student
     * OPTIMIZED: Eliminado N+1 queries, implementado eager loading completo, joins eficientes y caché
     */
    private function getMentorSuggestions()
    {
        // Early return con menos queries - cargar con eager loading
        $student = Auth::user()->load('aprendiz.areasInteres');
        
        if (!$student->aprendiz || $student->aprendiz->areasInteres->isEmpty()) {
            return [];
        }
        
        $studentAreaIds = $student->aprendiz->areasInteres->pluck('id');
        
        // CACHÉ INTELIGENTE: Múltiples niveles de cache con Redis
        $cacheKey = 'mentor_suggestions_' . md5($studentAreaIds->sort()->implode(','));
        $longTermCacheKey = 'mentor_pool_' . md5($studentAreaIds->sort()->implode(','));
        
        // Nivel 1: Cache rápido (2 minutos) para requests frecuentes
        $suggestions = Cache::remember($cacheKey, 120, function() use ($studentAreaIds, $longTermCacheKey) {
            // Nivel 2: Cache a largo plazo (10 minutos) para el pool de mentores
            return Cache::remember($longTermCacheKey, 600, function() use ($studentAreaIds) {
                return $this->buildMentorSuggestionsQuery($studentAreaIds);
            });
        });
        
        return $suggestions;
    }

    /**
     * Build the optimized mentor suggestions query
     */
    private function buildMentorSuggestionsQuery($studentAreaIds)
    {
        
        // OPTIMIZACIÓN CRÍTICA: Usar joins en lugar de whereHas + eager loading completo
        $mentors = User::select('users.id', 'users.name', 'mentors.calificacionPromedio')
            ->join('mentors', 'users.id', '=', 'mentors.user_id')
            ->join('mentor_area_interes', 'mentors.id', '=', 'mentor_area_interes.mentor_id')
            ->where('users.role', 'mentor')
            ->where('mentors.disponible_ahora', true)
            ->whereIn('mentor_area_interes.area_interes_id', $studentAreaIds)
            ->with([
                'mentor' => function($query) {
                    // Solo cargar campos necesarios
                    $query->select([
                        'id', 'user_id', 'experiencia', 'biografia', 'años_experiencia',
                        'disponibilidad', 'disponibilidad_detalle', 'disponible_ahora', 
                        'calificacionPromedio'
                    ]);
                },
                'mentor.areasInteres:id,nombre' // Solo campos necesarios de áreas
            ])
            ->orderByDesc('mentors.calificacionPromedio') // Ordenar por mejor calificación
            ->distinct() // Evitar duplicados por múltiples áreas en común
            ->limit(6)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mentor' => [
                        'experiencia' => $user->mentor->experiencia,
                        'biografia' => $user->mentor->biografia,
                        'años_experiencia' => $user->mentor->años_experiencia,
                        'disponibilidad' => $user->mentor->disponibilidad,
                        'disponibilidad_detalle' => $user->mentor->disponibilidad_detalle,
                        'disponible_ahora' => $user->mentor->disponible_ahora,
                        'calificacionPromedio' => $user->mentor->calificacionPromedio,
                        'stars_rating' => $user->mentor->stars_rating,
                        'rating_percentage' => $user->mentor->rating_percentage,
                        'areas_interes' => $user->mentor->areasInteres,
                    ]
                ];
            });
        
        return $mentors->toArray();
    }
}
