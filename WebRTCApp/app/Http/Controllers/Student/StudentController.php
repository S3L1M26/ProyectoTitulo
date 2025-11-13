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
        $student = Auth::user()->load('aprendiz');
        
        logger()->debug('📊 Student Dashboard accessed', [
            'user_id' => $student->id,
            'has_aprendiz' => $student->aprendiz !== null,
        ]);
        
        // Obtener solo las solicitudes pendientes para el modal de solicitud
        $solicitudes = \App\Models\SolicitudMentoria::where('estudiante_id', $student->id)
            ->where('estado', 'pendiente')
            ->get();
        
        // Cargar mentorías confirmadas del estudiante
        $mentoriasConfirmadas = \App\Models\Mentoria::where('aprendiz_id', $student->id)
            ->where('estado', 'confirmada')
            ->where('fecha', '>=', now()->toDateString())
            ->with(['mentor:id,name,email'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($mentoria) {
                return [
                    'id' => $mentoria->id,
                    'fecha' => $mentoria->fecha,
                    'hora' => $mentoria->hora,
                    'fecha_formateada' => $mentoria->fecha_formateada,
                    'hora_formateada' => $mentoria->hora_formateada,
                    'duracion_minutos' => $mentoria->duracion_minutos,
                    'enlace_reunion' => $mentoria->enlace_reunion,
                    'estado' => $mentoria->estado,
                    'mentor_id' => $mentoria->mentor_id,
                    'mentor' => [
                        'id' => $mentoria->mentor->id,
                        'name' => $mentoria->mentor->name,
                    ],
                ];
            });

        // Cargar historial de mentorías (completadas y canceladas)
        $mentoriasHistorial = \App\Models\Mentoria::where('aprendiz_id', $student->id)
            ->whereIn('estado', ['completada', 'cancelada'])
            ->with(['mentor:id,name,email'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->limit(20) // Últimas 20 mentorías
            ->get()
            ->map(function ($mentoria) {
                return [
                    'id' => $mentoria->id,
                    'fecha' => $mentoria->fecha,
                    'hora' => $mentoria->hora,
                    'fecha_formateada' => $mentoria->fecha_formateada,
                    'hora_formateada' => $mentoria->hora_formateada,
                    'duracion_minutos' => $mentoria->duracion_minutos,
                    'estado' => $mentoria->estado,
                    'mentor_id' => $mentoria->mentor_id,
                    'mentor' => [
                        'id' => $mentoria->mentor->id,
                        'name' => $mentoria->mentor->name,
                    ],
                ];
            });
        
        // Cargar sugerencias de mentores directamente (eager loading)
        // Lazy props no funcionan en primera carga - necesitan solicitud explícita del frontend
        $mentorSuggestions = $this->getMentorSuggestions();
        
        return Inertia::render('Student/Dashboard/Index', [
            // Datos críticos (siempre cargados) - optimizados con cache
            'aprendiz' => $student->aprendiz,
            'solicitudesPendientes' => $solicitudes,
            'mentorSuggestions' => $mentorSuggestions,
            'mentoriasConfirmadas' => $mentoriasConfirmadas,
            'mentoriasHistorial' => $mentoriasHistorial,
        ]);
    }

    /**
     * Get mentor suggestions for the authenticated student
     * OPTIMIZED: Eliminado N+1 queries, implementado eager loading completo, joins eficientes y caché
     * SECURITY: Requiere certificado verificado para acceder a sugerencias
     */
    private function getMentorSuggestions()
    {
        // Early return con menos queries - cargar con eager loading
        $student = Auth::user()->load('aprendiz.areasInteres');
        
        // Verificar certificado de alumno regular
        if (!$student->aprendiz || !$student->aprendiz->certificate_verified) {
            logger()->debug('� Mentor suggestions blocked: certificate not verified', [
                'user_id' => $student->id,
                'has_aprendiz' => $student->aprendiz !== null,
                'certificate_verified' => $student->aprendiz?->certificate_verified ?? false,
            ]);
            
            return [
                'requires_verification' => true,
                'message' => 'Debes verificar tu certificado de alumno regular para ver mentores.',
                'action' => 'upload_certificate',
                'upload_url' => route('profile.edit') . '#certificate',
                'mentors' => []
            ];
        }
        
        if ($student->aprendiz->areasInteres->isEmpty()) {
            logger()->debug('❌ No mentor suggestions: no areas of interest selected');
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
                        'calificacionPromedio', 'cv_verified'
                    ]);
                },
                'mentor.areasInteres:id,nombre', // Solo campos necesarios de áreas
                'mentorDocuments' => function($query) {
                    // Cargar solo el último documento aprobado y público
                    $query->where('status', 'approved')
                          ->where('is_public', true)
                          ->latest('processed_at')
                          ->limit(1);
                }
            ])
            ->orderByDesc('mentors.calificacionPromedio')
            ->distinct()
            ->limit(6)
            ->get()
            ->map(function($user) {
                // OPTIMIZACIÓN: Acceder a relaciones ya cargadas, evitar accessors pesados
                $mentorProfile = $user->mentor;
                $calificacion = $mentorProfile->calificacionPromedio ?? 0;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mentor' => [
                        'experiencia' => $mentorProfile->experiencia,
                        'biografia' => $mentorProfile->biografia,
                        'años_experiencia' => $mentorProfile->años_experiencia,
                        'disponibilidad' => $mentorProfile->disponibilidad,
                        'disponibilidad_detalle' => $mentorProfile->disponibilidad_detalle,
                        'disponible_ahora' => $mentorProfile->disponible_ahora,
                        'calificacionPromedio' => $calificacion,
                        // OPTIMIZACIÓN: Calcular aquí en lugar de usar accessors
                        'stars_rating' => round($calificacion, 1),
                        'rating_percentage' => ($calificacion / 5) * 100,
                        'areas_interes' => $mentorProfile->areasInteres->map(fn($a) => [
                            'id' => $a->id,
                            'nombre' => $a->nombre
                        ]),
                        'cv_verified' => $mentorProfile->cv_verified,
                        'has_public_cv' => $user->mentorDocuments->isNotEmpty(),
                    ]
                ];
            })
            ->toArray();
        
        return $mentors;
    }
}