<?php

namespace App\Providers;

use App\Events\MentoriaConfirmada;
use App\Listeners\EnviarNotificacionMentoriaConfirmada;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MentoriaConfirmada::class => [
            EnviarNotificacionMentoriaConfirmada::class,
        ],
    ];

    /**
     * Disable event auto-discovery to prevent duplicate listener registration
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        parent::boot();
        
        // OPTIMIZACIÓN: Solo loguear en debug mode y una vez
        if (config('app.debug') && !app()->runningInConsole()) {
            static $logged = false;
            if (!$logged) {
                try {
                    // Instrumentación: contar listeners registrados para MentoriaConfirmada
                    $dispatcher = $this->app['events'];
                    $listeners = $dispatcher->getListeners(MentoriaConfirmada::class);
                    Log::debug('🔍 EVENT LISTENER COUNT', [
                        'event' => MentoriaConfirmada::class,
                        'listener_count' => count($listeners),
                    ]);
                    $logged = true;
                } catch (\Throwable $e) {
                    Log::warning('No se pudo inspeccionar listeners del evento MentoriaConfirmada', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
