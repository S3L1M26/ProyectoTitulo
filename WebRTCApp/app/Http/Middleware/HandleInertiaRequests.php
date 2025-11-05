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
        if ($user) {
            // Asegurar datos mínimos para icono/banner de perfil en navbar sin cargar excesivo
            if ($user->role === 'student') {
                $user->loadMissing([
                    'aprendiz:id,user_id,semestre,objetivos',
                    'aprendiz.areasInteres:id',
                ]);
            } elseif ($user->role === 'mentor') {
                $user->loadMissing([
                    'mentor:id,user_id,experiencia,biografia,años_experiencia,disponibilidad',
                    'mentor.areasInteres:id',
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
            
            // CACHÉ: Completitud del perfil (5 minutos)
            if (in_array($user->role, ['student', 'mentor'])) {
                try {
                    $profileCompletenessData = Cache::remember(
                        'profile_completeness_' . $user->id,
                        300, // 5 minutos
                        function() use ($user) {
                            // Cargar relaciones solo cuando se calcula por primera vez
                            if ($user->role === 'student') {
                                $user->load(['aprendiz.areasInteres']);
                            } elseif ($user->role === 'mentor') {
                                $user->load(['mentor.areasInteres']);
                            }
                            return $user->profile_completeness;
                        }
                    );
                } catch (\Exception $e) {
                    logger()->error('Error calculating profile completeness in Inertia: ' . $e->getMessage());
                }
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'profile_completeness' => $profileCompletenessData,
            'contadorNoLeidas' => $contadorNoLeidas,
        ];
    }
}
