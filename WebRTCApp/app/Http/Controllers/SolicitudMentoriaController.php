<?php

namespace App\Http\Controllers;

use App\Models\Models\SolicitudMentoria;
use App\Models\User;
use App\Models\Mentor;
use App\Models\Aprendiz;
use App\Jobs\ProcessSolicitudMentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SolicitudMentoriaController extends Controller
{
    /**
     * Store a new mentorship request (student creates request).
     */
    public function store(Request $request)
    {
        $estudiante = Auth::user();
        
        // Validar que el estudiante tenga perfil de aprendiz
        $aprendiz = Aprendiz::where('user_id', $estudiante->id)->first();
        
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

        // Validar que el mentor exista y tenga perfil completo
        $mentor = Mentor::where('user_id', $validated['mentor_id'])->first();
        
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

        // Crear la solicitud
        $solicitud = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $validated['mentor_id'],
            'mensaje' => $validated['mensaje'] ?? null,
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
        ]);

        // Enviar notificación al mentor de forma asíncrona
        ProcessSolicitudMentoria::dispatch($solicitud, 'created');

        return redirect()->back()->with('success', 'Solicitud de mentoría enviada exitosamente.');
    }

    /**
     * List mentorship requests for the authenticated mentor.
     */
    public function index()
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
                'cv' => 'Debes tener tu CV verificado para ver solicitudes.',
            ]);
        }

        // Obtener solicitudes del mentor con información del estudiante
        $solicitudes = SolicitudMentoria::where('mentor_id', $mentor->id)
            ->with(['estudiante', 'aprendiz'])
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
        ProcessSolicitudMentoria::dispatch($solicitud, 'accepted');

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
        ProcessSolicitudMentoria::dispatch($solicitud, 'rejected');

        return redirect()->back()->with('success', 'Solicitud rechazada.');
    }

    /**
     * Get all solicitudes for the authenticated student.
     * Returns solicitudes with mentor information, ordered by newest first.
     */
    public function misSolicitudes()
    {
        $estudiante = Auth::user();
        
        // Obtener todas las solicitudes del estudiante con eager loading
        $solicitudes = SolicitudMentoria::where('estudiante_id', $estudiante->id)
            ->with([
                'mentorUser.mentor.areasInteres',
                'aprendiz.areasInteres'
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
                        'id' => $solicitud->mentorUser->id,
                        'name' => $solicitud->mentorUser->name,
                        'email' => $solicitud->mentorUser->email,
                        'años_experiencia' => $solicitud->mentorUser->mentor->años_experiencia,
                        'biografia' => $solicitud->mentorUser->mentor->biografia,
                        'experiencia' => $solicitud->mentorUser->mentor->experiencia,
                        'areas_interes' => $solicitud->mentorUser->mentor->areasInteres->map(function ($area) {
                            return [
                                'id' => $area->id,
                                'nombre' => $area->nombre,
                            ];
                        }),
                    ],
                    'areas_interes' => $solicitud->aprendiz->areasInteres->map(function ($area) {
                        return [
                            'id' => $area->id,
                            'nombre' => $area->nombre,
                        ];
                    }),
                ];
            });

        return inertia('Student/Dashboard/Index', [
            'misSolicitudes' => $solicitudes,
        ]);
    }

    /**
     * Get unread notifications for the authenticated student.
     */
    public function misNotificaciones()
    {
        $estudiante = Auth::user();
        
        // Obtener notificaciones no leídas
        $notificaciones = $estudiante->unreadNotifications()
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

        $contadorNoLeidas = $estudiante->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\SolicitudMentoriaAceptada',
                'App\Notifications\SolicitudMentoriaRechazada',
            ])
            ->count();

        return inertia('Student/Dashboard/Index', [
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

        return redirect()->back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
}
