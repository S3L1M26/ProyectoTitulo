<?php

namespace App\Http\Controllers;

use App\Models\VocationalSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VocationalSurveyController extends Controller
{
    /**
     * Listar historial de encuestas del estudiante autenticado.
     */
    public function index()
    {
        $studentId = Auth::id();

        $surveys = VocationalSurvey::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $surveys,
        ]);
    }

    /**
     * Mostrar el último snapshot (última encuesta) del estudiante.
     */
    public function show()
    {
        $studentId = Auth::id();

        $latest = VocationalSurvey::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'data' => $latest,
        ]);
    }

    /**
     * Guardar respuestas y calcular ICV (promedio preguntas 1-4).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'clarity_interest' => ['required', 'integer', 'between:1,5'],
            'confidence_area' => ['required', 'integer', 'between:1,5'],
            'platform_usefulness' => ['required', 'integer', 'between:1,5'],
            'mentorship_usefulness' => ['required', 'integer', 'between:1,5'],
            'recent_change_reason' => ['nullable', 'string', 'max:200'],
        ]);

        $icv = round((
            $validated['clarity_interest'] +
            $validated['confidence_area'] +
            $validated['platform_usefulness'] +
            $validated['mentorship_usefulness']
        ) / 4, 2);

        $survey = VocationalSurvey::create([
            'student_id' => Auth::id(),
            'clarity_interest' => $validated['clarity_interest'],
            'confidence_area' => $validated['confidence_area'],
            'platform_usefulness' => $validated['platform_usefulness'],
            'mentorship_usefulness' => $validated['mentorship_usefulness'],
            'recent_change_reason' => $validated['recent_change_reason'] ?? null,
            'icv' => $icv,
        ]);

        return redirect()
            ->route('student.vocational')
            ->with('success', 'Autoevaluación guardada');
    }
}
