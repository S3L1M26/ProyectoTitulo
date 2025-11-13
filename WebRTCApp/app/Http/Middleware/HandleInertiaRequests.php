<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $profileCompletenessData = null;
        $contadorNoLeidas = 0;
        
        // OPTIMIZACIÓN: Solo cargar datos necesarios y usar caché
        if (!$user) {
            return [
                ...parent::share($request),
                'auth' => ['user' => null],
            ];
        }
        
        // Inicializar variables compartidas
        $contadorNoLeidas = 0;
        $solicitudesPendientes = 0;
        $profileCompletenessData = null;

        // Asegurar datos mínimos para icono/banner de perfil en navbar sin cargar excesivo
        if ($user->role === 'student') {
            $user->loadMissing([
                'aprendiz:id,user_id,semestre,objetivos',
                'aprendiz.areasInteres:id,nombre',
            ]);
        } elseif ($user->role === 'mentor') {
            $user->loadMissing([
                'mentor:id,user_id,experiencia,biografia,años_experiencia,disponibilidad',
                'mentor.areasInteres:id,nombre',
            ]);
        }

        // CACHÉ: Contador de notificaciones (30 segundos)
        if ($user->role === 'student') {
            $contadorNoLeidas = Cache::remember(
                'student_unread_notifications_' . $user->id,
                30, // 30 segundos de cache
                function() use ($user) {
                    return $user->unreadNotifications()
                        ->whereIn('type', [
                            'App\Notifications\SolicitudMentoriaAceptada',
                            'App\Notifications\SolicitudMentoriaRechazada',
                        ])
                        ->count();
                }
            );
        }
        
            // CACHÉ: Contador de solicitudes pendientes para mentores (30 segundos)
            if ($user->role === 'mentor') {
                $solicitudesPendientes = Cache::remember(
                    'mentor_pending_solicitudes_' . $user->id,
                    30, // 30 segundos de cache
                    function() use ($user) {
                        return \App\Models\SolicitudMentoria::where('mentor_id', $user->id)
                            ->where('estado', 'pendiente')
                            ->count();
                    }
                );
            }
        
        // CACHÉ: Completitud del perfil (5 minutos) con fallback robusto
        if (in_array($user->role, ['student', 'mentor'])) {
            // Calcular siempre primero en memoria (accesor garantiza array)
            $rawCompleteness = $user->profile_completeness;
            try {
                $cached = Cache::remember(
                    'profile_completeness_' . $user->id,
                    300,
                    fn() => $rawCompleteness // Cierre sin lógica adicional para reducir riesgo de excepción
                );
                $profileCompletenessData = $cached ?? $rawCompleteness; // Fallback si por anomalía viene null
                if ($cached === null) {
                    logger()->debug('Profile completeness cache returned null; using raw fallback', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'raw_percentage' => $rawCompleteness['percentage'] ?? null,
                    ]);
                }
            } catch (\Exception $e) {
                logger()->error('Error calculating profile completeness in Inertia (fallback used): ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'exception' => $e->getMessage(),
                ]);
                $profileCompletenessData = $rawCompleteness; // Fallback directo
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                // Normalizar estructura para frontend (evitar pérdida de relaciones por nombre camelCase)
                'user' => $this->transformUserForFrontend($user),
            ],
            'profile_completeness' => $profileCompletenessData,
            'contadorNoLeidas' => $contadorNoLeidas,
                'solicitudesPendientes' => $solicitudesPendientes,
        ];
    }

    /**
     * Transform user model to a frontend friendly array preserving relations with consistent snake_case keys.
     */
    private function transformUserForFrontend($user): array
    {
        $base = $user->toArray();

        if ($user->relationLoaded('aprendiz') && $user->aprendiz) {
            $aprendiz = $user->aprendiz->toArray();
            // Forzar claves estándar
            $aprendiz['areas_interes'] = $user->aprendiz->areasInteres?->map(fn($a) => [
                'id' => $a->id,
                'nombre' => $a->nombre,
            ])->toArray() ?? [];
            $base['aprendiz'] = $aprendiz;
        }

        if ($user->relationLoaded('mentor') && $user->mentor) {
            $mentor = $user->mentor->toArray();
            $mentor['areas_interes'] = $user->mentor->areasInteres?->map(fn($a) => [
                'id' => $a->id,
                'nombre' => $a->nombre,
            ])->toArray() ?? [];
            $base['mentor'] = $mentor;
        }

        return $base;
    }
}
