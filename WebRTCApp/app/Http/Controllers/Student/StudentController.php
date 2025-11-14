<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MentorReview;
use App\Models\Mentoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        
        // CACHÉ INTELIGENTE: Cache solo datos básicos, NO el calificacionPromedio
        // El calificacionPromedio SIEMPRE se obtiene fresco de la BD
        $cacheKey = 'mentor_suggestions_' . md5($studentAreaIds->sort()->implode(','));
        $longTermCacheKey = 'mentor_pool_' . md5($studentAreaIds->sort()->implode(','));
        
        // Nivel 1: Cache rápido (2 minutos) para requests frecuentes - mentores sin promedios
        $mentorsBasicData = Cache::remember($cacheKey, 120, function() use ($studentAreaIds, $longTermCacheKey) {
            // Nivel 2: Cache a largo plazo (10 minutos) para el pool de mentores
            return Cache::remember($longTermCacheKey, 600, function() use ($studentAreaIds) {
                return $this->buildMentorSuggestionsQuery($studentAreaIds);
            });
        });
        
        // CRÍTICO: Obtener calificacionPromedio FRESCO de la BD (no cacheado)
        $mentorIds = collect($mentorsBasicData)->pluck('id')->toArray();
        $freshRatings = DB::table('mentors')
            ->whereIn('user_id', $mentorIds)
            ->pluck('calificacionPromedio', 'user_id');
        
        // CRÍTICO: Computar can_review FUERA del cache para que siempre sea fresco
        $student = auth()->user();
        $mentorRecordIds = DB::table('mentors')->whereIn('user_id', $mentorIds)->pluck('id')->toArray();
        
        $userReviews = MentorReview::whereIn('mentor_id', $mentorRecordIds)
            ->where('user_id', $student->id)
            ->get()
            ->keyBy('mentor_id');
        
        $completedMentorships = Mentoria::whereIn('mentor_id', $mentorIds)
            ->where('aprendiz_id', $student->id)
            ->where('estado', 'completada')
            ->pluck('mentor_id')
            ->toArray();

        logger()->debug('Review debug (fresh)', [
            'mentorIds' => $mentorIds,
            'mentorRecordIds' => $mentorRecordIds,
            'completedMentorships' => $completedMentorships,
            'userReviewsCount' => $userReviews->count(),
            'freshRatingsCount' => $freshRatings->count(),
        ]);

        // Enriquecer datos cacheados con can_review fresco + calificacionPromedio actualizado
        return array_map(function($mentor) use ($userReviews, $completedMentorships, $freshRatings) {
            $mentorUserId = $mentor['id'];
            $mentorProfileId = $mentor['mentor']['id'] ?? null;
            $userReview = $userReviews->get($mentorProfileId);
            $hasCompletedMentoria = in_array($mentorUserId, $completedMentorships);
            $canReview = $hasCompletedMentoria || ($userReview !== null);
            
            // CRÍTICO: Obtener el calificacionPromedio fresco de la BD, no del caché
            $freshRating = $freshRatings[$mentorUserId] ?? 0;
            
            $mentor['mentor']['user_review'] = $userReview ? [
                'id' => $userReview->id,
                'rating' => (int) $userReview->rating,
                'comment' => $userReview->comment,
                'created_at' => $userReview->created_at,
            ] : null;
            $mentor['mentor']['can_review'] = $canReview;
            $mentor['mentor']['calificacionPromedio'] = (float) $freshRating; // Siempre fresco
            $mentor['mentor']['stars_rating'] = round($freshRating, 1); // Recalcular
            $mentor['mentor']['rating_percentage'] = ($freshRating / 5) * 100; // Recalcular
            
            return $mentor;
        }, $mentorsBasicData);

    }

    /**
     * Build the optimized mentor suggestions query (cached mentor data without can_review or calificacionPromedio)
     * NOTE: calificacionPromedio is fetched FRESH in getMentorSuggestions(), never cached
     */
    private function buildMentorSuggestionsQuery($studentAreaIds)
    {
        // OPTIMIZACIÓN CRÍTICA: Usar joins en lugar de whereHas + eager loading completo
        // IMPORTANTE: NO seleccionar calificacionPromedio aquí - se obtiene fresco después
        $mentors = User::select('users.id', 'users.name')
            ->join('mentors', 'users.id', '=', 'mentors.user_id')
            ->join('mentor_area_interes', 'mentors.id', '=', 'mentor_area_interes.mentor_id')
            ->where('users.role', 'mentor')
            ->where('mentors.disponible_ahora', true)
            ->whereIn('mentor_area_interes.area_interes_id', $studentAreaIds)
            ->orderBy('mentors.calificacionPromedio', 'desc') // Ordenar por rating de mayor a menor
            ->with([
                'mentor' => function($query) {
                    // Solo cargar campos necesarios (SIN calificacionPromedio)
                    $query->select([
                        'id', 'user_id', 'experiencia', 'biografia', 'años_experiencia',
                        'disponibilidad', 'disponibilidad_detalle', 'disponible_ahora', 
                        'cv_verified'
                    ]);
                },
                'mentor.areasInteres:id,nombre', // Solo campos necesarios de áreas
                'mentor.reviews' => function($query) {
                    // Cargar SOLO la reseña más reciente para mostrar en valoraciones
                    $query->select('id', 'mentor_id', 'rating', 'comment', 'created_at')
                          ->latest('created_at')
                          ->limit(1);
                },
                'mentorDocuments' => function($query) {
                    // Cargar solo el último documento aprobado y público
                    $query->where('status', 'approved')
                          ->where('is_public', true)
                          ->latest('processed_at')
                          ->limit(1);
                }
            ])
            ->distinct()
            ->limit(6)
            ->get();

        // Retornar datos cacheables sin can_review ni calificacionPromedio (se computan fresco después)
        return $mentors->map(function($user) {
                // OPTIMIZACIÓN: Acceder a relaciones ya cargadas, evitar accessors pesados
                $mentorProfile = $user->mentor;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mentor' => [
                        'id' => $mentorProfile->id, // Agregado para batch queries posteriores
                        'experiencia' => $mentorProfile->experiencia,
                        'biografia' => $mentorProfile->biografia,
                        'años_experiencia' => $mentorProfile->años_experiencia,
                        'disponibilidad' => $mentorProfile->disponibilidad,
                        'disponibilidad_detalle' => $mentorProfile->disponibilidad_detalle,
                        'disponible_ahora' => $mentorProfile->disponible_ahora,
                        'calificacionPromedio' => 0, // Placeholder, se reemplaza con dato fresco
                        'stars_rating' => 0, // Placeholder
                        'rating_percentage' => 0, // Placeholder
                        'areas_interes' => $mentorProfile->areasInteres->map(fn($a) => [
                            'id' => $a->id,
                            'nombre' => $a->nombre
                        ]),
                        'cv_verified' => $mentorProfile->cv_verified,
                        'has_public_cv' => $user->mentorDocuments->isNotEmpty(),
                        // Reseñas anónimas para mostrar en modal (pre-cargadas en eager load)
                        'anonymized_reviews' => $mentorProfile->reviews->map(fn($r) => [
                            'id' => $r->id,
                            'rating' => (int) $r->rating,
                            'comment' => $r->comment,
                            'created_at' => $r->created_at,
                        ])->all(),
                        'user_review' => null, // Se enriquece después
                        'can_review' => false, // Se enriquece después con lógica fresca
                    ]
                ];
            })
            ->toArray();
    }
}