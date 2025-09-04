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
    public function create(string $role): Response
    {
        $view = match($role) {
            'student' => 'Auth/Student/Login',
            'mentor' => 'Auth/Mentor/Login',
            default => 'Auth/Login'
        };

        return Inertia::render($view, [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if(Auth::user()->role === 'admin') {
            return redirect(route('admin.dashboard'));
        }

        // Redireccionar según el rol
        $redirectRoute = match(Auth::user()->role) {
            'mentor' => 'mentor.dashboard',
            'student' => 'student.dashboard',
            default => 'dashboard'
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
