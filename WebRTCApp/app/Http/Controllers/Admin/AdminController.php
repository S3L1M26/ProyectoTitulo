<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        // Eager loading optimizado con solo campos necesarios
        $users = User::with([
            'aprendiz:id,user_id,certificate_verified',
            'mentor:id,user_id,cv_verified,disponible_ahora'
        ])
        ->select('id', 'name', 'email', 'role', 'created_at', 'is_active')
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            
            // Lazy prop: Estadísticas solo si se solicitan
            'stats' => fn () => Cache::remember('admin_stats', 600, function() {
                return [
                    'total_users' => User::count(),
                    'total_students' => User::where('role', 'student')->count(),
                    'total_mentors' => User::where('role', 'mentor')->count(),
                    'verified_students' => \App\Models\Aprendiz::where('certificate_verified', true)->count(),
                    'verified_mentors' => \App\Models\Mentor::where('cv_verified', true)->count(),
                ];
            }),
        ]);
    }

    public function show(User $user)
    {
        // Eager loading solo de relaciones necesarias con campos específicos
        $user->load([
            'aprendiz.areasInteres:id,nombre',
            'mentor.areasInteres:id,nombre'
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user
        ]);
    }

    public function edit(User $user)
    {
        // Eager loading solo de relaciones necesarias
        $user->load([
            'aprendiz.areasInteres:id,nombre',
            'mentor.areasInteres:id,nombre'
        ]);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'is_active' => 'boolean',
        ]);

        $user->update($validated);
        
        // Invalidar caché de estadísticas
        Cache::forget('admin_stats');

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        // Invalidar caché de estadísticas
        Cache::forget('admin_stats');

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
