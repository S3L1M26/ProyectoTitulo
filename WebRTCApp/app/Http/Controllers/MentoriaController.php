<?php

namespace App\Http\Controllers;

use App\Events\MentoriaConfirmada;
use App\Exceptions\ZoomApiException;
use App\Exceptions\ZoomAuthException;
use App\Http\Requests\ConfirmarMentoriaRequest;
use App\Mail\MentoriaCanceladaMail;
use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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
        // Correlation ID (frontend puede enviar X-CID, si no lo genera backend)
        $cid = $request->header('X-CID') ?? uniqid('cid_');
        $reqId = uniqid('req_');

            // 🔒 CANDADO DE IDEMPOTENCIA: Evitar doble dispatch por mismo CID
            $cacheKey = "mentoria_confirmada_{$cid}";
            if (Cache::has($cacheKey)) {
                Log::warning('⏩ EVITADO DOBLE DISPATCH', [
                    'cid' => $cid,
                    'solicitud_id' => $solicitud->id,
                    'reason' => 'CID ya procesado previamente',
                ]);
                return back()->with('status', 'Mentoría ya confirmada');
            }

            // Marcar CID como procesado (TTL 120 segundos)
            Cache::put($cacheKey, true, 120);

        Log::info('🎯 CONFIRMAR MENTORIA CALLED', [
            'solicitud_id' => $solicitud->id,
            'timestamp' => microtime(true),
            'request_id' => $reqId,
            'cid' => $cid,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $request->only(['fecha','hora','duracion_minutos','topic']),
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

            // Invalidar caché de solicitudes del mentor
            Cache::forget('mentor_solicitudes_' . $solicitud->mentor_id);

            // Disparar evento
            Log::info('📢 DESPACHANDO EVENTO MentoriaConfirmada', [
                'mentoria_id' => $mentoria->id,
                'timestamp' => microtime(true),
                'cid' => $cid,
            ]);
            MentoriaConfirmada::dispatch($mentoria, $cid);

            Log::info('📬 EVENTO DESPACHADO', [
                'mentoria_id' => $mentoria->id,
                'cid' => $cid,
                'timestamp' => microtime(true),
            ]);

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

    /**
     * Cancelar una mentoría confirmada: elimina la reunión en Zoom (best-effort) y limpia datos locales.
     */
    public function cancelar(Request $request, Mentoria $mentoria)
    {
        $user = Auth::user();
        if ($user->id !== $mentoria->mentor_id) {
            return response()->json(['message' => 'No autorizado para cancelar esta mentoría.'], 403);
        }
        if ($mentoria->estado !== 'confirmada') {
            return response()->json(['message' => 'Solo mentorías confirmadas pueden cancelarse.'], 422);
        }

        $zoomId = $mentoria->zoom_meeting_id;
        $erroresZoom = null;
        if ($zoomId) {
            try {
                $this->zoom->cancelarReunion($zoomId);
            } catch (ZoomApiException|ZoomAuthException $e) {
                Log::channel('zoom')->warning('Fallo al cancelar reunión Zoom (continuando cancel local)', [
                    'mentoria_id' => $mentoria->id,
                    'zoom_meeting_id' => $zoomId,
                    'error' => $e->getMessage(),
                ]);
                $erroresZoom = $e->getMessage();
            }
        }

        // Actualizar estado de mentoría
        $mentoria->estado = 'cancelada';
        $mentoria->enlace_reunion = null;
        $mentoria->zoom_meeting_id = null;
        $mentoria->zoom_password = null;
        $mentoria->save();

        // Actualizar estado de la solicitud a 'cancelada' para permitir reagendar
        if ($mentoria->solicitud_id) {
            $solicitud = SolicitudMentoria::find($mentoria->solicitud_id);
            if ($solicitud) {
                $solicitud->estado = 'cancelada';
                $solicitud->save();
                
                // Enviar notificación por correo al aprendiz
                try {
                    Mail::to($solicitud->estudiante->email)->send(new MentoriaCanceladaMail($mentoria, $solicitud));
                } catch (\Exception $e) {
                    Log::error('Error al enviar correo de mentoría cancelada', [
                        'mentoria_id' => $mentoria->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Limpiar caché relacionado
        Cache::forget('mentor_solicitudes_' . $user->id);
        Cache::forget('student_solicitudes_' . $mentoria->aprendiz_id);

        Log::info('Mentoría cancelada', [
            'mentoria_id' => $mentoria->id,
            'mentor_id' => $user->id,
            'solicitud_estado_actualizado' => 'cancelada',
            'errores_zoom' => $erroresZoom,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mentoría cancelada',
                'mentoria' => $mentoria->refresh(),
                'zoom_error' => $erroresZoom,
            ]);
        }

        return back()->with('status', 'Mentoría cancelada');
    }
}
