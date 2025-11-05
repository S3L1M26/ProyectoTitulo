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
        
        // Obtener todas las solicitudes del estudiante con eager loading
        $solicitudes = \App\Models\Models\SolicitudMentoria::where('estudiante_id', $student->id)
            ->with([
                'mentorUser.mentor.areasInteres',
                'aprendiz.areasInteres'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'id' => $solicitud->id,
                    'estado' => $solicitud->estado,
                    'mensaje' => $solicitud->mensaje,
                    'fecha_solicitud' => $solicitud->fecha_solicitud,
                    'fecha_respuesta' => $solicitud->fecha_respuesta,
                    'created_at' => $solicitud->created_at,
                    'updated_at' => $solicitud->updated_at,
                    'mentor' => [
                        'id' => $solicitud->mentorUser->id,
                        'name' => $solicitud->mentorUser->name,
                        'años_experiencia' => $solicitud->mentorUser->mentor->años_experiencia ?? 0,
                        'biografia' => $solicitud->mentorUser->mentor->biografia ?? '',
                        'areas_interes' => $solicitud->mentorUser->mentor->areasInteres->map(function ($area) {
                            return [
                                'id' => $area->id,
                                'nombre' => $area->nombre,
                            ];
                        }),
                    ],
                ];
            });
        
        // Obtener notificaciones no leídas de solicitudes de mentoría
        $notificaciones = $student->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\SolicitudMentoriaAceptada',
                'App\Notifications\SolicitudMentoriaRechazada',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                ];
            });
        
        $contadorNoLeidas = $student->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\SolicitudMentoriaAceptada',
                'App\Notifications\SolicitudMentoriaRechazada',
            ])
            ->count();
        
        return Inertia::render('Student/Dashboard/Index', [
            'mentorSuggestions' => $this->getMentorSuggestions(),
            'aprendiz' => $student->aprendiz,
            'solicitudesPendientes' => $solicitudes->where('estado', 'pendiente')->values(),
            'misSolicitudes' => $solicitudes,
            'notificaciones' => $notificaciones,
            'contadorNoLeidas' => $contadorNoLeidas,
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
        
        // VALIDACIÓN: Verificar que el estudiante tenga certificado verificado
        if (!$student->aprendiz || !$student->aprendiz->certificate_verified) {
            // Retornar estructura vacía para Inertia (se manejará en el frontend)
            return [
                'requires_verification' => true,
                'message' => 'Debes verificar tu certificado de alumno regular para ver mentores.',
                'action' => 'upload_certificate',
                'upload_url' => route('profile.edit') . '#certificate',
                'mentors' => []
            ];
        }
        
        if ($student->aprendiz->areasInteres->isEmpty()) {
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
                        'cv_verified' => $user->mentor->cv_verified,
                        'has_public_cv' => $user->mentorDocuments->isNotEmpty(),
                    ]
                ];
            });
        
        return $mentors->toArray();
    }
}
