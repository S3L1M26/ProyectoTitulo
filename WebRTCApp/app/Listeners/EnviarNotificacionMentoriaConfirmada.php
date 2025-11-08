<?php

namespace App\Listeners;

use App\Events\MentoriaConfirmada;
use App\Jobs\EnviarCorreoMentoria;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// Listener ejecuta síncronamente para evitar double-queuing
// El Job EnviarCorreoMentoria ya se encola, así que no necesitamos encolar el listener también
class EnviarNotificacionMentoriaConfirmada
{
    public function handle(MentoriaConfirmada $event): void
    {
        $listenerId = uniqid('listener_');

        // Idempotencia a nivel de listener: evitar doble ejecución para misma mentoría + cid
        $lockKey = 'mentoria_listener_lock_' . $event->mentoria->id . '_' . $event->cid;
        if (Cache::has($lockKey)) {
            Log::warning('⛔ LISTENER DUPLICATE SKIP', [
                'mentoria_id' => $event->mentoria->id,
                'cid' => $event->cid,
                'listener_id' => $listenerId,
                'timestamp' => microtime(true),
            ]);
            return; // Salir sin re-despachar job
        }
        Cache::put($lockKey, true, 120); // 120s TTL para evitar repeticiones cercanas y liberar memoria antes
        Log::info('🔔 LISTENER EJECUTADO', [
            'mentoria_id' => $event->mentoria->id,
            'timestamp' => microtime(true),
            'listener_id' => $listenerId,
            'cid' => $event->cid,
        ]);
        
        try {
            $jobDispatchId = uniqid('job_');
            EnviarCorreoMentoria::dispatch($event->mentoria, $event->cid, $jobDispatchId)->onQueue('emails');
            Log::info('📨 JOB ENCOLADO', [
                'mentoria_id' => $event->mentoria->id,
                'cid' => $event->cid,
                'listener_id' => $listenerId,
                'job_dispatch_id' => $jobDispatchId,
                'timestamp' => microtime(true),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al encolar correo de mentoría', [
                'mentoria_id' => $event->mentoria->id ?? null,
                'cid' => $event->cid ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
