<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;
    
    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend($notifiable, $channel): bool
    {
        // Idempotencia: verificar si ya se envió recientemente
        $lockKey = 'verify_email_notification_' . $notifiable->id;
        
        if (Cache::has($lockKey)) {
            Log::warning('⛔ EMAIL VERIFICATION NOTIFICATION DUPLICATE SKIP', [
                'user_id' => $notifiable->id,
                'user_email' => $notifiable->email,
                'channel' => $channel,
                'timestamp' => microtime(true),
            ]);
            return false;
        }
        
        // Establecer lock por 60 segundos (1 minuto)
        Cache::put($lockKey, true, 60);
        
        Log::info('📧 SENDING EMAIL VERIFICATION NOTIFICATION', [
            'user_id' => $notifiable->id,
            'user_email' => $notifiable->email,
            'channel' => $channel,
            'timestamp' => microtime(true),
        ]);
        
        return true;
    }
    
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verificar Dirección de Correo Electrónico')
            ->greeting('¡Hola!')
            ->line('Por favor, haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
            ->action('Verificar Correo Electrónico', $verificationUrl)
            ->line('Si no creaste una cuenta, no es necesario realizar ninguna acción.')
            ->salutation('Saludos,<br>'.config('app.name'));
    }
}
