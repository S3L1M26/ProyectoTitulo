<?php

namespace App\Jobs;

use App\Models\SolicitudMentoria;
use App\Models\Mentor;
use App\Notifications\SolicitudMentoriaRecibida;
use App\Notifications\SolicitudMentoriaAceptada;
use App\Notifications\SolicitudMentoriaRechazada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class ProcessSolicitudMentoria implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

        /**
     * The mentorship request ID.
     *
     * @var int
     */
    public $solicitud_id;

    /**
     * The action to perform.
     *
     * @var string
     */
    public $action;

    /**
     * Create a new job instance.
     */
    public function __construct(int $solicitud_id, string $action = 'created')
    {
        $this->solicitud_id = $solicitud_id;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Cargar la solicitud con todas las relaciones necesarias
        $solicitud = SolicitudMentoria::with(['estudiante', 'mentor', 'mentorProfile'])
            ->findOrFail($this->solicitud_id);
        
        switch ($this->action) {
            case 'created':
                // Enviar notificación al mentor (sincronamente)
                $solicitud->mentor->notifyNow(new SolicitudMentoriaRecibida($solicitud));
                break;

            case 'accepted':
                // Enviar notificación al estudiante (sincronamente)
                $solicitud->estudiante->notifyNow(new SolicitudMentoriaAceptada($solicitud));
                // INVALIDAR CACHÉ del estudiante para reflejar la nueva notificación/estado
                Cache::forget('student_notifications_' . $solicitud->estudiante_id);
                Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
                Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
                
                // Verificar si el mentor alcanzó su límite de solicitudes aceptadas
                $this->updateMentorAvailability($solicitud);
                break;

            case 'rejected':
                // Enviar notificación al estudiante (sincronamente)
                $solicitud->estudiante->notifyNow(new SolicitudMentoriaRechazada($solicitud));
                // INVALIDAR CACHÉ del estudiante para reflejar la nueva notificación/estado
                Cache::forget('student_notifications_' . $solicitud->estudiante_id);
                Cache::forget('student_unread_notifications_' . $solicitud->estudiante_id);
                Cache::forget('student_solicitudes_' . $solicitud->estudiante_id);
                break;
        }
    }

    /**
     * Update mentor availability if limit is reached.
     */
    protected function updateMentorAvailability(SolicitudMentoria $solicitud): void
    {
        $mentorProfile = Mentor::where('user_id', $solicitud->mentor_id)->first();
        
        if (!$mentorProfile) {
            return;
        }

        // Contar solicitudes aceptadas del mentor
        $solicitudesAceptadas = SolicitudMentoria::where('mentor_id', $solicitud->mentor_id)
            ->where('estado', 'aceptada')
            ->count();

        // Si alcanza 5 solicitudes aceptadas, marcar como no disponible
        if ($solicitudesAceptadas >= 5) {
            $mentorProfile->update([
                'disponible_ahora' => false,
            ]);
        }
    }
}
