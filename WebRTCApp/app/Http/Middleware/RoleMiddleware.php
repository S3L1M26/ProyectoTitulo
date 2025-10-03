<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if ($user->role !== $role) {
            // Redirigir al dashboard apropiado según el rol del usuario
            $redirectRoute = match($user->role) {
                'admin' => 'admin.dashboard',
                'mentor' => 'mentor.dashboard',
                'student' => 'student.dashboard',
                default => 'login'
            };

            return redirect()->route($redirectRoute);
        }

        return $next($request);
    }
}