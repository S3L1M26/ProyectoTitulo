<?php

namespace App\Notifications;

use App\Models\Models\SolicitudMentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudMentoriaRechazada extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the notification may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying.
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
     * Create a new notification instance.
     */
    public function __construct(SolicitudMentoria $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mentor = $this->solicitud->mentor;
        $mentorProfile = $this->solicitud->mentorProfile;
        
        // Obtener motivo si existe (podríamos agregarlo al modelo más adelante)
        $motivo = $this->solicitud->motivo_rechazo ?? null;
        
        $message = (new MailMessage)
            ->subject('Actualización sobre tu solicitud de mentoría')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Queremos informarte sobre el estado de tu solicitud de mentoría con **' . $mentor->name . '**.')
            ->line('Lamentablemente, en este momento tu solicitud no ha podido ser aceptada.')
            ->line('---');
        
        // Si hay motivo, incluirlo
        if ($motivo) {
            $message->line('### 📝 Motivo')
                ->line($motivo)
                ->line('---');
        }
        
        $message->line('### 💡 ¿Qué puedes hacer ahora?')
            ->line('**No te desanimes.** Esto puede deberse a limitaciones de tiempo, disponibilidad actual del mentor o a que buscan perfiles más específicos en este momento.')
            ->line('**Te sugerimos:**')
            ->line('• 🔍 **Explorar otros mentores** disponibles en la plataforma')
            ->line('• 👥 **Revisar perfiles** de mentores con áreas de interés similares a las tuyas')
            ->line('• 📧 **Contactar a otros profesionales** que puedan ayudarte')
            ->line('• ⏰ **Intentar nuevamente** en otra ocasión cuando el mentor tenga más disponibilidad')
            ->line('---')
            ->action('🔎 Buscar otros mentores', url('/student/dashboard'))
            ->line('---')
            ->line('Recuerda que hay muchos mentores excelentes en nuestra plataforma esperando para ayudarte en tu desarrollo profesional. ¡No te rindas!')
            ->salutation('Con el mejor de los ánimos,<br>Equipo de ' . config('app.name'));
        
        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'solicitud_id' => $this->solicitud->id,
            'mentor_id' => $this->solicitud->mentor_id,
            'mentor_nombre' => $this->solicitud->mentor->name,
            'mentor_experiencia' => $this->solicitud->mentorProfile->años_experiencia ?? null,
            'fecha_respuesta' => $this->solicitud->fecha_respuesta,
            'motivo_rechazo' => $this->solicitud->motivo_rechazo ?? null,
            'estado' => 'rechazada',
            'tipo' => 'SolicitudMentoriaRechazada',
        ];
    }
}
