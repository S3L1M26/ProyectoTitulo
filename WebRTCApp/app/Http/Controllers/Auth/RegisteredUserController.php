<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(string $role): Response
    {
        if (!in_array($role, ['student', 'mentor'])) {
            abort(404);
        }

        return Inertia::render("Auth/$role/Register");
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validación base para todos los usuarios
        $baseValidation = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Validaciones específicas según el rol
        $roleValidations = [
            'mentor' => [
                'experiencia' => 'required|string',
                'especialidad' => 'required|string',
                'disponibilidad' => 'required|string',
            ],
            'student' => [
                'semestre' => 'required|integer|min:1|max:10',
                'intereses' => 'required|array|min:1',
                'intereses.*' => 'string',
            ],
        ];

        // Combinar validaciones según el rol
        $validations = array_merge(
            $baseValidation, 
            $roleValidations[$request->role] ?? []
        );

        $validated = $request->validate($validations);

        // Crear usuario base
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $request->role,
        ]);

        // Crear perfil específico según rol
        if ($request->role === 'mentor') {
            $user->mentor()->create([
                'experiencia' => $validated['experiencia'],
                'especialidad' => $validated['especialidad'],
                'disponibilidad' => $validated['disponibilidad'],
                'calificacionPromedio' => 0.0,
            ]);
        } elseif ($request->role === 'student') {
            $user->aprendiz()->create([
                'semestre' => $validated['semestre'],
                'intereses' => $validated['intereses'],
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route($request->role === 'mentor' ? 'mentor.dashboard' : 'student.dashboard');
    }
}
