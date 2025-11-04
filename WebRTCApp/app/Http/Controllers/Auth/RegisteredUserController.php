<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $role): Response|RedirectResponse
    {
        $role = $role->query('role', 'student');

        if (!in_array($role, ['student', 'mentor'])) {
            abort(404);
        }

        if (Auth::check()) {
            return redirect()->route(Auth::user()->role === 'mentor' ? 'mentor.dashboard' : 'student.dashboard');
        }

        return Inertia::render('Auth/Register', [
            'role' => $role,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validación básica para registro inicial
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:student,mentor',
        ]);

        // Crear usuario base
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Crear perfiles básicos vacíos que se completarán después
        if ($validated['role'] === 'mentor') {
            $user->mentor()->create([
                'experiencia' => null,
                'biografia' => null,
                'años_experiencia' => null,
                'disponibilidad' => null,
                'disponibilidad_detalle' => null,
                'disponible_ahora' => false,
                'calificacionPromedio' => 0.0,
            ]);
        } else {
            $user->aprendiz()->create([
                'semestre' => null,
                'objetivos' => null,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route($validated['role'] === 'mentor' ? 'mentor.dashboard' : 'student.dashboard');
    }
}
