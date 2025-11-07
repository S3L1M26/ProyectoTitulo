<?php

namespace App\Listeners;

use App\Events\MentoriaConfirmada;
use App\Jobs\EnviarCorreoMentoria;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

// Listener ejecuta síncronamente para evitar double-queuing
// El Job EnviarCorreoMentoria ya se encola, así que no necesitamos encolar el listener también
class EnviarNotificacionMentoriaConfirmada
{
    public function handle(MentoriaConfirmada $event): void
    {
        try {
            EnviarCorreoMentoria::dispatch($event->mentoria)->onQueue('emails');
        } catch (\Throwable $e) {
            Log::error('Error al encolar correo de mentoría', [
                'mentoria_id' => $event->mentoria->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
