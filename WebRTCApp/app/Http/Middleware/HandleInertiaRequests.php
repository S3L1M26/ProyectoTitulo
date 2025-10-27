<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
        
        // Cargar relaciones según el rol para el cálculo de progreso
        if ($user) {
            if ($user->role === 'student') {
                $user->load(['aprendiz.areasInteres']);
            } elseif ($user->role === 'mentor') {
                $user->load(['mentor.areasInteres']);
            }
            
            // Calcular completitud del perfil si es estudiante o mentor
            if (in_array($user->role, ['student', 'mentor'])) {
                try {
                    $profileCompletenessData = $user->profile_completeness;
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
        ];
    }
}
