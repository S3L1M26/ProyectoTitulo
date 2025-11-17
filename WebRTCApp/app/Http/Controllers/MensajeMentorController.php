<?php

namespace App\Http\Controllers;

use App\Mail\MensajeMentorMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MensajeMentorController extends Controller
{
    /**
     * Enviar mensaje al mentor si el estudiante tiene relación (solicitud aceptada o mentoría confirmada).
     */
    public function store(Request $request, User $mentor)
    {
        $student = $request->user();
        abort_unless($student && $student->role === 'student', 403);

        if (!$this->canContactUser($student->id, $mentor->id)) {
            return back()->withErrors(['contacto' => 'No autorizado para contactar a este mentor.']);
        }

        $data = $request->validate([
            'asunto'  => ['required', 'string', 'max:150'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        // Enviar correo (queue en prod si hay configurado)
        try {
            Mail::to($mentor->email)->send(new MensajeMentorMail(
                student: $student,
                mentor: $mentor,
                asunto: $data['asunto'],
                mensaje: $data['mensaje']
            ));
        } catch (\Exception $e) {
            Log::error('Error enviando MensajeMentorMail', [
                'student_id' => $student->id,
                'mentor_id' => $mentor->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['contacto' => 'No se pudo enviar el mensaje. Inténtalo luego.']);
        }

        return back()->with('success', 'Mensaje enviado correctamente.');
    }

    /**
     * Listar mentores contactables por el estudiante autenticado.
     */
    public function contactables(Request $request)
    {
        $student = $request->user();
        abort_unless($student && $student->role === 'student', 403);

        $mentorIdsAceptadas = DB::table('solicitud_mentorias')
            ->where('aprendiz_id', $student->id)
            ->whereIn('estado', ['aceptada'])
            ->pluck('mentor_id');

        $mentorIdsConfirmadas = DB::table('mentorias')
            ->join('solicitud_mentorias', 'mentorias.solicitud_id', '=', 'solicitud_mentorias.id')
            ->where('solicitud_mentorias.aprendiz_id', $student->id)
            ->where('mentorias.estado', 'confirmada')
            ->pluck('solicitud_mentorias.mentor_id');

        $mentorIds = $mentorIdsAceptadas->merge($mentorIdsConfirmadas)->unique()->values();

        $mentores = User::whereIn('id', $mentorIds)->get(['id', 'name', 'email']);

        return response()->json([
            'mentores' => $mentores,
        ]);
    }

    /**
     * Indicar si el estudiante puede contactar a un mentor específico.
     */
    public function canContact(Request $request, User $mentor)
    {
        $student = $request->user();
        abort_unless($student && $student->role === 'student', 403);
        return response()->json([
            'can' => $this->canContactUser($student->id, $mentor->id),
        ]);
    }

    /**
     * Regla de autorización: solicitud aceptada o mentoría confirmada.
     */
    private function canContactUser(int $studentId, int $mentorId): bool
    {
        $tieneAceptada = DB::table('solicitud_mentorias')
            ->where('estudiante_id', $studentId)
            ->where('mentor_id', $mentorId)
            ->whereIn('estado', ['aceptada'])
            ->exists();

        $tieneConfirmada = DB::table('mentorias')
            ->join('solicitud_mentorias', 'mentorias.solicitud_id', '=', 'solicitud_mentorias.id')
            ->where('solicitud_mentorias.estudiante_id', $studentId)
            ->where('solicitud_mentorias.mentor_id', $mentorId)
            ->where('mentorias.estado', 'confirmada')
            ->exists();

        return $tieneAceptada || $tieneConfirmada;
    }
}
