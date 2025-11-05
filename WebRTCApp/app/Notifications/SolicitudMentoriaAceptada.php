<?php

namespace App\Notifications;

use App\Models\Models\SolicitudMentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudMentoriaAceptada extends Notification implements ShouldQueue
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
        
        return (new MailMessage)
            ->subject('¡Tu solicitud de mentoría ha sido aceptada!')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('¡Buenas noticias! Tu solicitud de mentoría ha sido aceptada.')
            ->line('**Datos del mentor:**')
            ->line('Nombre: ' . $mentor->name)
            ->line('Email: ' . $mentor->email)
            ->line('Experiencia: ' . ($mentorProfile ? $mentorProfile->años_experiencia . ' años' : 'No especificado'))
            ->line('**Próximos pasos:**')
            ->line('1. Revisa el perfil completo de tu mentor en el dashboard')
            ->line('2. Coordina una primera reunión con tu mentor')
            ->line('3. Prepara tus objetivos y expectativas para la mentoría')
            ->action('Ver detalles en tu dashboard', url('/dashboard'))
            ->line('Te recomendamos establecer contacto lo antes posible para comenzar tu proceso de mentoría.')
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
            'estado' => 'aceptada',
        ];
    }
}
