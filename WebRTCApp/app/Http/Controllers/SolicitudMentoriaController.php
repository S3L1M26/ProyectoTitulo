<?php

namespace App\Http\Controllers;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Models\Mentor;
use App\Models\Aprendiz;
use App\Jobs\ProcessSolicitudMentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SolicitudMentoriaController extends Controller
{
    /**
     * Store a new mentorship request (student creates request).
     */
    public function store(Request $request)
    {
        $estudiante = Auth::user();
        
        // Eager loading optimizado: cargar aprendiz en una sola query
        $aprendiz = Aprendiz::select('id', 'user_id', 'certificate_verified')
            ->where('user_id', $estudiante->id)
            ->first();
        
        if (!$aprendiz) {
            throw ValidationException::withMessages([
                'perfil' => 'Debes completar tu perfil de aprendiz antes de solicitar mentoría.',
            ]);
        }

        // Validar que el estudiante tenga certificado verificado
        if (!$aprendiz->certificate_verified) {
            throw ValidationException::withMessages([
                'certificado' => 'Debes tener tu certificado verificado antes de solicitar mentoría.',
            ]);
        }

        // Validar datos de la solicitud
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'mensaje' => 'nullable|string|max:1000',
        ]);

        // Eager loading optimizado: Solo campos necesarios del mentor
        $mentor = Mentor::select('id', 'user_id', 'cv_verified', 'disponible_ahora')
            ->where('user_id', $validated['mentor_id'])
            ->first();
        
        if (!$mentor) {
            throw ValidationException::withMessages([
                'mentor' => 'El mentor seleccionado no tiene un perfil válido.',
            ]);
        }

        // Validar que el mentor tenga CV verificado
        if (!$mentor->cv_verified) {
            throw ValidationException::withMessages([
                'mentor' => 'El mentor debe tener su CV verificado para recibir solicitudes.',
            ]);
        }

        // Validar que el mentor esté disponible
        if (!$mentor->disponible_ahora) {
            throw ValidationException::withMessages([
                'disponibilidad' => 'El mentor no está disponible en este momento.',
            ]);
        }

        // Validar que no exista una solicitud pendiente duplicada
        $solicitudExistente = SolicitudMentoria::where('estudiante_id', $estudiante->id)
            ->where('mentor_id', $validated['mentor_id'])
            ->where('estado', 'pendiente')
            ->exists();

        if ($solicitudExistente) {
            throw ValidationException::withMessages([
                'solicitud' => 'Ya tienes una solicitud pendiente con este mentor.',
            ]);
        }

        // Validar que no exista una mentoría confirmada activa con este mentor
        if (SolicitudMentoria::tieneMentoriaActivaConMentor($estudiante->id, $validated['mentor_id'])) {
            throw ValidationException::withMessages([
                'mentoria_activa' => 'Ya tienes una mentoría activa con este mentor. Espera a que el mentor la marque como concluida para solicitar una nueva sesión.',
            ]);
        }

        // Crear la solicitud
        $solicitud = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $validated['mentor_id'],
            'mensaje' => $validated['mensaje'] ?? null,
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
        ]);

        // Enviar notificación al mentor de forma asíncrona
        ProcessSolicitudMentoria::dispatchSync($solicitud->id, 'created');

        // INVALIDAR CACHÉ: Listas dependientes del estudiante y mentor
        Cache::forget('student_solicitudes_' . $estudiante->id);
        Cache::forget('mentor_solicitudes_' . $validated['mentor_id']);

        return redirect()->back()->with('success', 'Solicitud de mentoría enviada exitosamente.');
    }

    /**
     * List mentorship requests for the authenticated mentor.
     */
    public function index()
    {
        $mentor = Auth::user();
        
        // Eager loading optimizado: Solo campos necesarios
        $mentorProfile = Mentor::select('id', 'user_id', 'cv_verified')
            ->where('user_id', $mentor->id)
            ->first();
        
        if (!$mentorProfile) {
            throw ValidationException::withMessages([
                'perfil' => 'No tienes un perfil de mentor.',
            ]);
        }

        // Validar que el mentor tenga CV verificado
        if (!$mentorProfile->cv_verified) {
            throw ValidationException::withMessages([
                'cv' => 'Debes tener tu CV verificado para ver solicitudes.',
            ]);
        }

        // Obtener solicitudes del mentor con eager loading optimizado
        $solicitudes = SolicitudMentoria::where('mentor_id', $mentor->id)
            ->with([
                'estudiante:id,name,email',
                'aprendiz:id,user_id,certificate_verified'
            ])
            ->orderBy('fecha_solicitud', 'desc')
            ->get();

        return redirect()->route('mentor.dashboard');
    }

    /**
     * Accept a mentorship request.
     */
    public function accept(Request $request, $id)
    {
        $mentor = Auth::user();
        
        // Validar que el usuario tenga perfil de mentor
        $mentorProfile = Mentor::where('user_id', $mentor->id)->first();
        
        if (!$mentorProfile) {
            throw ValidationException::withMessages([
                'perfil' => 'No tienes un perfil de mentor.',
            ]);
        }

        // Validar que el mentor tenga CV verificado
        if (!$mentorProfile->cv_verified) {
            throw ValidationException::withMessages([
                'cv' => 'Debes tener tu CV verificado para gestionar solicitudes.',
            ]);
        }

        // Buscar la solicitud
        $solicitud = SolicitudMentoria::findOrFail($id);

        // Validar que la solicitud pertenezca al mentor autenticado
        if ($solicitud->mentor_id !== $mentor->id) {
            throw ValidationException::withMessages([
                'autorizacion' => 'No tienes autorización para gestionar esta solicitud.',
            ]);
        }

        // Validar que la solicitud esté pendiente
        if ($solicitud->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'estado' => 'Esta solicitud ya ha sido procesada.',
            ]);
        }

        // Aceptar la solicitud
        $solicitud->update([
            'estado' => 'aceptada',
            'fecha_respuesta' => now(),
        ]);

        // Enviar notificación al estudiante de forma asíncrona
        ProcessSolicitudMentoria::dispatchSync($solicitud->id, 'accepted');

        // INVALIDAR CACHÉ del estudiante afectado
        Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
        Cache::forget('student_notifications_' . $solicitud->estudiante_id);
        Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
        
        // INVALIDAR CACHÉ del mentor (contador del navbar)
        Cache::forget('mentor_solicitudes_' . $solicitud->mentor_id);
        Cache::forget('mentor_pending_solicitudes_' . $solicitud->mentor_id);

        return redirect()->back()->with('success', 'Solicitud aceptada exitosamente.');
    }

    /**
     * Reject a mentorship request.
     */
    public function reject(Request $request, $id)
    {
        $mentor = Auth::user();
        
        // Validar que el usuario tenga perfil de mentor
        $mentorProfile = Mentor::where('user_id', $mentor->id)->first();
        
        if (!$mentorProfile) {
            throw ValidationException::withMessages([
                'perfil' => 'No tienes un perfil de mentor.',
            ]);
        }

        // Validar que el mentor tenga CV verificado
        if (!$mentorProfile->cv_verified) {
            throw ValidationException::withMessages([
                'cv' => 'Debes tener tu CV verificado para gestionar solicitudes.',
            ]);
        }

        // Buscar la solicitud
        $solicitud = SolicitudMentoria::findOrFail($id);

        // Validar que la solicitud pertenezca al mentor autenticado
        if ($solicitud->mentor_id !== $mentor->id) {
            throw ValidationException::withMessages([
                'autorizacion' => 'No tienes autorización para gestionar esta solicitud.',
            ]);
        }

        // Validar que la solicitud esté pendiente
        if ($solicitud->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'estado' => 'Esta solicitud ya ha sido procesada.',
            ]);
        }

        // Rechazar la solicitud
        $solicitud->update([
            'estado' => 'rechazada',
            'fecha_respuesta' => now(),
        ]);

        // Enviar notificación al estudiante de forma asíncrona
        ProcessSolicitudMentoria::dispatchSync($solicitud->id, 'rejected');

        // INVALIDAR CACHÉ del estudiante afectado
        Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
        Cache::forget('student_notifications_' . $solicitud->estudiante_id);
        Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
        
        // INVALIDAR CACHÉ del mentor (contador del navbar)
        Cache::forget('mentor_solicitudes_' . $solicitud->mentor_id);
        Cache::forget('mentor_pending_solicitudes_' . $solicitud->mentor_id);

        return redirect()->back()->with('success', 'Solicitud rechazada.');
    }

    /**
     * Get all solicitudes for the authenticated student.
     * Returns solicitudes with mentor information, ordered by newest first.
     * OPTIMIZED: Caché y eager loading selectivo
     */
    public function misSolicitudes()
    {
        $estudiante = Auth::user();
        
        $solicitudes = $this->buildSolicitudesCollection($estudiante->id);

        return inertia('Student/Solicitudes/Index', [
            'misSolicitudes' => $solicitudes,
            'polling' => [
                'interval_ms' => 10000,
            ],
        ]);
    }

    /**
     * Endpoint de polling (API) para solicitudes del estudiante con soporte de etag.
     * Devuelve 200 con cambios o 304 sin cambios.
     */
    public function pollSolicitudes(Request $request)
    {
        $estudiante = Auth::user();
        $current = $this->buildSolicitudesCollection($estudiante->id);

        $etag = sha1($current->pluck('id')->join('-').':'.$current->max('updated_at'));
        $clientEtag = $request->query('etag');

        if ($clientEtag && hash_equals($clientEtag, $etag)) {
            return response()->json([
                'etag' => $etag,
                'changed' => false,
            ], 200); // usamos 200 JSON consistente (en vez de 304 para simplificar caches intermedios)
        }

        return response()->json([
            'etag' => $etag,
            'changed' => true,
            'items' => $current,
        ]);
    }

    private function buildSolicitudesCollection(int $estudianteId)
    {
        return SolicitudMentoria::where('estudiante_id', $estudianteId)
            ->with([
                'mentor:id,name,email',
                'mentor.mentor:id,user_id,años_experiencia,biografia,experiencia',
                'mentor.mentor.areasInteres:id,nombre',
                'aprendiz.areasInteres:id,nombre',
                'mentoria'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'id' => $solicitud->id,
                    'estado' => $solicitud->estado,
                    'mensaje' => $solicitud->mensaje,
                    'fecha_solicitud' => $solicitud->fecha_solicitud,
                    'fecha_respuesta' => $solicitud->fecha_respuesta,
                    'created_at' => $solicitud->created_at,
                    'updated_at' => $solicitud->updated_at,
                    'mentor' => [
                        'id' => $solicitud->mentor->id,
                        'name' => $solicitud->mentor->name,
                        'años_experiencia' => $solicitud->mentor->mentor->años_experiencia ?? 0,
                        'biografia' => $solicitud->mentor->mentor->biografia ?? '',
                        'areas_interes' => $solicitud->mentor->mentor->areasInteres->map(function ($area) {
                            return [
                                'id' => $area->id,
                                'nombre' => $area->nombre,
                            ];
                        }),
                    ],
                    'mentoria' => $solicitud->mentoria ? [
                        'id' => $solicitud->mentoria->id,
                        'fecha_formateada' => $solicitud->mentoria->fecha_formateada,
                        'hora_formateada' => $solicitud->mentoria->hora_formateada,
                        'enlace_reunion' => $solicitud->mentoria->enlace_reunion,
                        'estado' => $solicitud->mentoria->estado,
                    ] : null,
                ];
            });
    }

    /**
     * Get unread notifications for the authenticated student.
     * OPTIMIZED: Caché de 1 minuto
     */
    public function misNotificaciones()
    {
        $estudiante = Auth::user();
        
        // CACHÉ: 1 minuto para notificaciones
        $notificaciones = Cache::remember(
            'student_notifications_' . $estudiante->id,
            60, // 1 minuto
            function() use ($estudiante) {
                return $estudiante->unreadNotifications()
                    ->whereIn('type', [
                        'App\Notifications\SolicitudMentoriaAceptada',
                        'App\Notifications\SolicitudMentoriaRechazada',
                    ])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($notification) {
                        return [
                            'id' => $notification->id,
                            'type' => class_basename($notification->type),
                            'data' => $notification->data,
                            'created_at' => $notification->created_at,
                            'read_at' => $notification->read_at,
                        ];
                    });
            }
        );

        $contadorNoLeidas = Cache::remember(
            'student_unread_notifications_' . $estudiante->id,
            30, // 30 segundos
            function() use ($estudiante) {
                return $estudiante->unreadNotifications()
                    ->whereIn('type', [
                        'App\Notifications\SolicitudMentoriaAceptada',
                        'App\Notifications\SolicitudMentoriaRechazada',
                    ])
                    ->count();
            }
        );

        return inertia('Student/Notifications/Index', [
            'notificaciones' => $notificaciones,
            'contadorNoLeidas' => $contadorNoLeidas,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function marcarComoLeida($id)
    {
        $estudiante = Auth::user();
        
        $notification = $estudiante->unreadNotifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
            // INVALIDAR CACHÉ de notificaciones para el estudiante
            Cache::forget('student_notifications_' . $estudiante->id);
            Cache::forget('student_unread_notifications_' . $estudiante->id);
            return redirect()->back()->with('success', 'Notificación marcada como leída.');
        }

        throw ValidationException::withMessages([
            'notificacion' => 'Notificación no encontrada.',
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated student.
     */
    public function marcarTodasComoLeidas()
    {
        $estudiante = Auth::user();
        
        $estudiante->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\SolicitudMentoriaAceptada',
                'App\Notifications\SolicitudMentoriaRechazada',
            ])
            ->update(['read_at' => now()]);

        // INVALIDAR CACHÉ de notificaciones para el estudiante
        Cache::forget('student_notifications_' . $estudiante->id);
        Cache::forget('student_unread_notifications_' . $estudiante->id);

        return redirect()->back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }

    /**
     * Verificar si el estudiante tiene una mentoría activa con un mentor específico.
     */
    public function hasActiveMentoria(Request $request, User $mentor)
    {
        $estudiante = $request->user();

        $hasActive = SolicitudMentoria::tieneMentoriaActivaConMentor($estudiante->id, $mentor->id);

        return response()->json([
            'hasActiveMentoria' => $hasActive,
        ]);
    }
}
