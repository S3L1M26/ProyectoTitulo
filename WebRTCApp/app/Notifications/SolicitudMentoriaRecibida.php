<?php

namespace App\Notifications;

use App\Models\Models\SolicitudMentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudMentoriaRecibida extends Notification implements ShouldQueue
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
        $estudiante = $this->solicitud->estudiante;
        $aprendiz = $this->solicitud->aprendiz;
        
        return (new MailMessage)
            ->subject('Nueva Solicitud de Mentoría')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Has recibido una nueva solicitud de mentoría.')
            ->line('**Datos del estudiante:**')
            ->line('Nombre: ' . $estudiante->name)
            ->line('Email: ' . $estudiante->email)
            ->line('Semestre: ' . ($aprendiz ? $aprendiz->semestre : 'No especificado'))
            ->line('**Mensaje del estudiante:**')
            ->line($this->solicitud->mensaje ?? 'Sin mensaje')
            ->action('Ver solicitud en tu dashboard', url('/dashboard'))
            ->line('Puedes aceptar o rechazar esta solicitud desde tu panel de control.')
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
            'estudiante_id' => $this->solicitud->estudiante_id,
            'estudiante_nombre' => $this->solicitud->estudiante->name,
            'mensaje' => $this->solicitud->mensaje,
            'fecha_solicitud' => $this->solicitud->fecha_solicitud,
        ];
    }
}
