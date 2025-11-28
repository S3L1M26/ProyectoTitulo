<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Detectar si es login de administrador y fijar rol por defecto
        $isAdminRoute = $request->routeIs('admin.login');
        $role = $isAdminRoute ? 'admin' : $request->query('role', 'student');

        return Inertia::render('Auth/Login', [
            'role' => $role,
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'allowAdmin' => $isAdminRoute,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $isAdminRoute = $request->routeIs('admin.login.store');
        $requestedRole = $request->input('role', $isAdminRoute ? 'admin' : null);

        $request->authenticate();

        $userRole = Auth::user()->role;

        if ($isAdminRoute && $userRole !== 'admin') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Solo administradores pueden ingresar aquí.',
            ]);
        }

        if (! $isAdminRoute) {
            // Evitar que un admin ingrese por el login público
            if ($userRole === 'admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Usa el acceso de administrador.',
                ]);
            }

            // Verificar que el rol del usuario coincida con el rol solicitado
            if ($requestedRole && $userRole !== $requestedRole) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Las credenciales no corresponden al tipo de usuario seleccionado.',
                ]);
            }
        }

        $request->session()->regenerate();

        // Verificar si hay una URL de destino específica
        $intendedUrl = $request->query('intended');
        if ($intendedUrl) {
            session(['url.intended' => url($intendedUrl)]);
        }

        // Redireccionar según el rol o a la URL intendida
        $redirectRoute = match(Auth::user()->role) {
            'mentor' => 'mentor.dashboard',
            'student' => 'student.dashboard',
            'admin' => 'admin.dashboard',
            default => 'login'
        };

        return redirect()->intended(route($redirectRoute));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
