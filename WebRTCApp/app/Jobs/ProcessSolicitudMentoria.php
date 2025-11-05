<?php

namespace App\Jobs;

use App\Models\Models\SolicitudMentoria;
use App\Models\Mentor;
use App\Notifications\SolicitudMentoriaRecibida;
use App\Notifications\SolicitudMentoriaAceptada;
use App\Notifications\SolicitudMentoriaRechazada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSolicitudMentoria implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

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
                
                // Verificar si el mentor alcanzó su límite de solicitudes aceptadas
                $this->updateMentorAvailability();
                break;

            case 'rejected':
                // Enviar notificación al estudiante
                $this->solicitud->estudiante->notify(new SolicitudMentoriaRechazada($this->solicitud));
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
