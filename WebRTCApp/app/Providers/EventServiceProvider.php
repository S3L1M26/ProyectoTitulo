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
        try {
            // Instrumentación: contar listeners registrados para MentoriaConfirmada
            $dispatcher = $this->app['events'];
            $listeners = $dispatcher->getListeners(MentoriaConfirmada::class);
            Log::info('🔍 EVENT LISTENER COUNT', [
                'event' => MentoriaConfirmada::class,
                'listener_count' => count($listeners),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo inspeccionar listeners del evento MentoriaConfirmada', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
