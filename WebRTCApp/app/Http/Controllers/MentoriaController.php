<?php

namespace App\Http\Controllers;

use App\Events\MentoriaConfirmada;
use App\Exceptions\ZoomApiException;
use App\Exceptions\ZoomAuthException;
use App\Http\Requests\ConfirmarMentoriaRequest;
use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MentoriaController extends Controller
{
    public function __construct(private ZoomService $zoom)
    {
    }

    /**
     * Confirmar una mentoría creando la reunión en Zoom y guardando el registro.
     */
    public function confirmar(ConfirmarMentoriaRequest $request, SolicitudMentoria $solicitud)
    {
        Log::info('🎯 CONFIRMAR MENTORIA CALLED', [
            'solicitud_id' => $solicitud->id,
            'timestamp' => microtime(true),
            'request_id' => uniqid('req_'),
        ]);
        
        // Verificar autorización (Gate definido en AppServiceProvider)
        $this->authorize('mentoria.confirmar', $solicitud);

        // Combinar fecha y hora en una instancia Carbon usando timezone provista o la de app
        $tz = $request->input('timezone', config('app.timezone', 'UTC'));
        $start = Carbon::createFromFormat('Y-m-d H:i', $request->string('fecha') . ' ' . $request->string('hora'), $tz);

        // Validar que no sea pasado (seguridad adicional a las rules)
        if ($start->isPast()) {
            return response()->json([
                'message' => 'La fecha/hora no puede ser en el pasado.'
            ], 422);
        }

        try {
            $topic = $request->input('topic', 'Mentoría');
            $zoomMeeting = $this->zoom->crearReunion([
                'topic' => $topic,
                'start_time' => $start->toIso8601String(),
                'duration' => (int) $request->input('duracion_minutos'),
                'timezone' => $tz,
            ]);

            // Crear registro de mentoría
            $mentoria = Mentoria::create([
                'solicitud_id' => $solicitud->id,
                'aprendiz_id' => $solicitud->estudiante_id,
                'mentor_id' => $solicitud->mentor_id,
                'fecha' => $start->copy()->setTimezone(config('app.timezone', 'UTC'))->toDateString(),
                'hora' => $start->copy()->setTimezone(config('app.timezone', 'UTC'))->toDateTimeString(),
                'duracion_minutos' => (int) $request->input('duracion_minutos'),
                'enlace_reunion' => $zoomMeeting['join_url'] ?? null,
                'zoom_meeting_id' => isset($zoomMeeting['id']) ? (string) $zoomMeeting['id'] : null,
                'zoom_password' => $zoomMeeting['password'] ?? null,
                'estado' => 'confirmada',
            ]);

            // Actualizar estado de la solicitud si procede
            if ($solicitud->estado !== 'aceptada') {
                $solicitud->aceptar();
            }

            // Disparar evento
            Log::info('📢 DESPACHANDO EVENTO MentoriaConfirmada', [
                'mentoria_id' => $mentoria->id,
                'timestamp' => microtime(true),
            ]);
            MentoriaConfirmada::dispatch($mentoria);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Mentoría confirmada con éxito.',
                    'mentoria' => $mentoria->refresh(),
                ], 201);
            }

            return back()->with('status', 'Mentoría confirmada');
        } catch (ZoomAuthException|ZoomApiException $e) {
            Log::channel('zoom')->error('Error al crear reunión de Zoom', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo crear la reunión de Zoom. Intenta más tarde.',
            ], 502);
        }
    }

    /**
     * Generar un enlace de Zoom sin guardar en DB (preview)
     */
    public function generarEnlacePreview(Request $request)
    {
        $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'duracion_minutos' => ['required', 'integer', 'min:30', 'max:180'],
            'topic' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string'],
        ]);

        $tz = $request->input('timezone', config('app.timezone', 'UTC'));
        $start = Carbon::createFromFormat('Y-m-d H:i', $request->string('fecha') . ' ' . $request->string('hora'), $tz);
        if ($start->isPast()) {
            return response()->json(['message' => 'La fecha/hora no puede ser en el pasado.'], 422);
        }

        try {
            $zoomMeeting = $this->zoom->crearReunion([
                'topic' => $request->input('topic', 'Mentoría (preview)'),
                'start_time' => $start->toIso8601String(),
                'duration' => (int) $request->input('duracion_minutos'),
                'timezone' => $tz,
            ]);

            return response()->json([
                'join_url' => $zoomMeeting['join_url'] ?? null,
                'id' => $zoomMeeting['id'] ?? null,
                'password' => $zoomMeeting['password'] ?? null,
            ]);
        } catch (ZoomAuthException|ZoomApiException $e) {
            Log::channel('zoom')->error('Error al crear reunión de Zoom (preview)', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'No se pudo generar el enlace.'], 502);
        }
    }

    /**
     * Redirigir al enlace de Zoom validando permisos.
     */
    public function unirse(Mentoria $mentoria)
    {
        $this->authorize('mentoria.unirse', $mentoria);

        if ($mentoria->estado === 'cancelada') {
            return back()->withErrors(['mentoria' => 'La mentoría fue cancelada.']);
        }

        Log::info('Acceso a mentoría', [
            'mentoria_id' => $mentoria->id,
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->away($mentoria->enlace_reunion);
    }
}
