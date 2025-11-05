<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Mentor;
use App\Models\Models\SolicitudMentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MentorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $mentorProfile = Mentor::where('user_id', $user->id)->first();
        
        // Obtener solicitudes del mentor con relaciones
        $solicitudes = SolicitudMentoria::where('mentor_id', $user->id)
            ->with(['estudiante', 'aprendiz.areasInteres'])
            ->orderBy('fecha_solicitud', 'desc')
            ->get();

        return Inertia::render('Mentor/Dashboard/Index', [
            'solicitudes' => $solicitudes,
            'mentorProfile' => $mentorProfile,
        ]);
    }
}
