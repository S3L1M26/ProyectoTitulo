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
        $mentor = $this->solicitud->mentorUser;
        $mentorProfile = $this->solicitud->mentorProfile;
        
        return (new MailMessage)
            ->subject('¡Tu solicitud de mentoría ha sido aceptada! 🎉')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tenemos excelentes noticias: **' . $mentor->name . '** ha aceptado ser tu mentor.')
            ->line('---')
            ->line('### 📋 Datos del mentor')
            ->line('**Nombre:** ' . $mentor->name)
            ->line('**Experiencia:** ' . ($mentorProfile ? $mentorProfile->años_experiencia . ' años' : 'No especificado'))
            ->line('**Biografía:** ' . ($mentorProfile->biografia ? substr($mentorProfile->biografia, 0, 150) . '...' : 'Ver perfil completo'))
            ->line('---')
            ->line('### ✅ Próximos pasos')
            ->line('1. **Revisa el perfil completo** de tu mentor en el dashboard')
            ->line('2. **Coordina una primera reunión** con ' . $mentor->name)
            ->line('3. **Prepara tus objetivos** y expectativas para la mentoría')
            ->line('4. **Establece un plan de trabajo** junto a tu mentor')
            ->action('🚀 Ver mi Dashboard', url('/student/dashboard'))
            ->line('---')
            ->line('💡 **Consejo:** Te recomendamos establecer contacto lo antes posible para comenzar tu proceso de mentoría. Una buena comunicación es clave para el éxito.')
            ->line('---')
            ->salutation('¡Mucho éxito en tu mentoría!<br>Equipo de ' . config('app.name'));
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
            'mentor_nombre' => $this->solicitud->mentorUser->name,
            'mentor_experiencia' => $this->solicitud->mentorProfile->años_experiencia ?? null,
            'fecha_respuesta' => $this->solicitud->fecha_respuesta,
            'estado' => 'aceptada',
            'tipo' => 'SolicitudMentoriaAceptada',
        ];
    }
}
