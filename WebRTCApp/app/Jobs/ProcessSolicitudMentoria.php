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
     * The mentorship request instance.
     *
     * @var SolicitudMentoria
     */
    public $solicitud;

    /**
     * The action to perform.
     *
     * @var string
     */
    public $action;

    /**
     * Create a new job instance.
     */
    public function __construct(SolicitudMentoria $solicitud, string $action = 'created')
    {
        $this->solicitud = $solicitud;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->action) {
            case 'created':
                // Enviar notificación al mentor
                $this->solicitud->mentor->notify(new SolicitudMentoriaRecibida($this->solicitud));
                break;

            case 'accepted':
                // Enviar notificación al estudiante
                $this->solicitud->estudiante->notify(new SolicitudMentoriaAceptada($this->solicitud));
                // INVALIDAR CACHÉ del estudiante para reflejar la nueva notificación/estado
                Cache::forget('student_notifications_' . $this->solicitud->estudiante_id);
                Cache::forget('student_unread_notifications_' . $this->solicitud->estudiante_id);
                Cache::forget('student_solicitudes_' . $this->solicitud->estudiante_id);
                
                // Verificar si el mentor alcanzó su límite de solicitudes aceptadas
                $this->updateMentorAvailability();
                break;

            case 'rejected':
                // Enviar notificación al estudiante
                $this->solicitud->estudiante->notify(new SolicitudMentoriaRechazada($this->solicitud));
                // INVALIDAR CACHÉ del estudiante para reflejar la nueva notificación/estado
                Cache::forget('student_notifications_' . $this->solicitud->estudiante_id);
                Cache::forget('student_unread_notifications_' . $this->solicitud->estudiante_id);
                Cache::forget('student_solicitudes_' . $this->solicitud->estudiante_id);
                break;
        }
    }

    /**
     * Update mentor availability if limit is reached.
     */
    protected function updateMentorAvailability(): void
    {
        $mentorProfile = Mentor::where('user_id', $this->solicitud->mentor_id)->first();
        
        if (!$mentorProfile) {
            return;
        }

        // Contar solicitudes aceptadas del mentor
        $solicitudesAceptadas = SolicitudMentoria::where('mentor_id', $this->solicitud->mentor_id)
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
