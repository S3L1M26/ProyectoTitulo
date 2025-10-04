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
                // Usar el nuevo método centralizado del modelo
                $profileCompletenessData = $user->profile_completeness;
                
                // Agregar tanto el porcentaje como los datos completos a la sesión
                session([
                    'profile_completeness' => $profileCompletenessData['percentage'],
                    'profile_completeness_data' => $profileCompletenessData
                ]);
            } catch (\Exception $e) {
                // Si hay error, simplemente continuar sin bloquear la respuesta
                logger()->error('Error calculating profile completeness: ' . $e->getMessage());
            }
        }

        return $response;
    }


}
