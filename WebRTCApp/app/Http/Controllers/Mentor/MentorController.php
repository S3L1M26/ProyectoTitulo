<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Mentor;
use App\Models\SolicitudMentoria;
use App\Models\Mentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MentorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Cargar perfil del mentor
        $mentorProfile = Mentor::where('user_id', $user->id)->first();
        
        // Cargar solicitudes con cache
        $solicitudes = Cache::remember(
            'mentor_solicitudes_' . $user->id,
            300, // 5 minutos
            fn () => SolicitudMentoria::where('mentor_id', $user->id)
                ->with(['estudiante:id,name,email', 'aprendiz.areasInteres:id,nombre'])
                ->orderBy('fecha_solicitud', 'desc')
                ->get()
        );
        
        // Cargar mentorías programadas del mentor
        $mentoriasProgramadas = Mentoria::where('mentor_id', $user->id)
            ->where('estado', 'confirmada')
            ->where('fecha', '>=', now()->toDateString())
            ->with(['aprendiz:id,name,email'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($mentoria) {
                return [
                    'id' => $mentoria->id,
                    'fecha' => $mentoria->fecha,
                    'hora' => $mentoria->hora,
                    'fecha_formateada' => $mentoria->fecha_formateada,
                    'hora_formateada' => $mentoria->hora_formateada,
                    'duracion_minutos' => $mentoria->duracion_minutos,
                    'enlace_reunion' => $mentoria->enlace_reunion,
                    'estado' => $mentoria->estado,
                    'aprendiz' => [
                        'id' => $mentoria->aprendiz->id,
                        'name' => $mentoria->aprendiz->name,
                    ],
                ];
            });
        
        return Inertia::render('Mentor/Dashboard/Index', [
            'mentorProfile' => $mentorProfile,
            'solicitudes' => $solicitudes,
            'mentoriasProgramadas' => $mentoriasProgramadas,
        ]);
    }
}
