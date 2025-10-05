<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        return Inertia::render('Student/Dashboard/Index', [
            'mentorSuggestions' => $this->getMentorSuggestions(),
        ]);
    }

    /**
     * Get mentor suggestions for the authenticated student
     */
    private function getMentorSuggestions()
    {
        $student = Auth::user()->aprendiz;
        
        if (!$student || !$student->areasInteres) {
            return [];
        }
        
        $studentAreaIds = $student->areasInteres->pluck('id');
        
        $mentors = User::where('role', 'mentor')
            ->whereHas('mentor', function($query) {
                $query->where('disponible_ahora', true);
            })
            ->whereHas('mentor.areasInteres', function($query) use ($studentAreaIds) {
                $query->whereIn('area_interes_id', $studentAreaIds);
            })
            ->with(['mentor.areasInteres'])
            ->limit(6)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mentor' => [
                        'experiencia' => $user->mentor->experiencia,
                        'biografia' => $user->mentor->biografia,
                        'años_experiencia' => $user->mentor->años_experiencia,
                        'disponibilidad' => $user->mentor->disponibilidad,
                        'disponibilidad_detalle' => $user->mentor->disponibilidad_detalle,
                        'disponible_ahora' => $user->mentor->disponible_ahora,
                        'calificacionPromedio' => $user->mentor->calificacionPromedio,
                        'stars_rating' => $user->mentor->stars_rating,
                        'rating_percentage' => $user->mentor->rating_percentage,
                        'areas_interes' => $user->mentor->areasInteres,
                    ]
                ];
            });
        
        return $mentors->toArray();
    }
}
