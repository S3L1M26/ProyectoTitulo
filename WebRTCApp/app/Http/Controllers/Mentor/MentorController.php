<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Mentor;
use App\Models\Models\SolicitudMentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MentorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        return Inertia::render('Mentor/Dashboard/Index', [
            // Datos críticos (siempre cargados)
            'mentorProfile' => fn () => Mentor::where('user_id', $user->id)->first(),
            
            // Lazy prop: Solo se carga si el componente lo solicita
            'solicitudes' => fn () => Cache::remember(
                'mentor_solicitudes_' . $user->id,
                300, // 5 minutos
                fn () => SolicitudMentoria::where('mentor_id', $user->id)
                    ->with(['estudiante:id,name,email', 'aprendiz.areasInteres:id,nombre'])
                    ->orderBy('fecha_solicitud', 'desc')
                    ->get()
            ),
        ]);
    }
}
