<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileIncompleteReminder extends Notification
{
    use Queueable;

    private $profileData;

    /**
     * Create a new notification instance.
     */
    public function __construct($profileData)
    {
        $this->profileData = $profileData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isStudent = $notifiable->role === 'student';
        $percentage = $this->profileData['percentage'];
        $missingFields = $this->profileData['missing_fields'];
        
        // Construir la URL del perfil de forma más confiable
        // Forzar la URL correcta en caso de problemas con Docker
        $baseUrl = config('app.url');
        if (str_contains($baseUrl, 'devtunnels.ms') || str_contains($baseUrl, 'ngrok')) {
            $baseUrl = 'http://localhost:8000';
        }
        $profileUrl = $baseUrl . '/profile';
        
        $message = (new MailMessage)
            ->subject('Completa tu perfil en Connect')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Hemos notado que tu perfil está incompleto al ' . $percentage . '%.')
            ->line($isStudent 
                ? 'Completar tu perfil te ayudará a recibir mejores recomendaciones de mentores.'
                : 'Completar tu perfil te ayudará a atraer más estudiantes.'
            );
            
        // Agregar campos faltantes de forma más legible
        if (!empty($missingFields)) {
            $message->line('Te faltan estos campos por completar:');
            foreach ($missingFields as $field) {
                $message->line('• ' . $field);
            }
        }
        
        return $message
            ->action('Completar Perfil', $profileUrl)
            ->line('¡Gracias por usar Connect!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'profile_percentage' => $this->profileData['percentage'],
            'missing_fields' => $this->profileData['missing_fields'],
        ];
    }
}