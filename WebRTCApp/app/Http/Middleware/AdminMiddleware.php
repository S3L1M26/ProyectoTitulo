<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Redirige según el rol del usuario
        if (Auth::check()) {
            $role = Auth::user()->role;
            if ($role === 'mentor') {
                return redirect()->route('mentor.dashboard');
            } elseif ($role === 'student') {
                return redirect()->route('student.dashboard');
            }
        }
        
        // Redirect non-admins to user dashboard instead of back
        return redirect()->route('login');
    }
}
