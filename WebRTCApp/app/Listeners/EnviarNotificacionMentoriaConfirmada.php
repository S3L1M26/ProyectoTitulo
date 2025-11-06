<?php

namespace App\Listeners;

use App\Events\MentoriaConfirmada;
use App\Jobs\EnviarCorreoMentoria;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionMentoriaConfirmada implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'emails';

    public function handle(MentoriaConfirmada $event): void
    {
        try {
            EnviarCorreoMentoria::dispatch($event->mentoria)->onQueue($this->queue);
        } catch (\Throwable $e) {
            Log::error('Fallo al encolar EnviarCorreoMentoria', [
                'mentoria_id' => $event->mentoria->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
