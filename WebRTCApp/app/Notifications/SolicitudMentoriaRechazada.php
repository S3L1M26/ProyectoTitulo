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
        
        return (new MailMessage)
            ->subject('Actualización sobre tu solicitud de mentoría')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Lamentamos informarte que tu solicitud de mentoría no ha sido aceptada en este momento.')
            ->line('**Mentor:** ' . $mentor->name)
            ->line('Esto puede deberse a limitaciones de tiempo o disponibilidad del mentor.')
            ->line('**Te sugerimos:**')
            ->line('• Explorar otros mentores disponibles en la plataforma')
            ->line('• Revisar perfiles de mentores con áreas de interés similares')
            ->line('• Intentar nuevamente en otra ocasión')
            ->action('Buscar otros mentores', url('/mentores'))
            ->line('No te desanimes, hay muchos mentores excelentes esperando por ti.')
            ->salutation('Saludos,<br>' . config('app.name'));
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
            'fecha_respuesta' => $this->solicitud->fecha_respuesta,
            'estado' => 'rechazada',
        ];
    }
}
