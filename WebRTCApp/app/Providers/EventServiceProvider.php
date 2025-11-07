<?php

namespace App\Providers;

use App\Events\MentoriaConfirmada;
use App\Listeners\EnviarNotificacionMentoriaConfirmada;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
}
