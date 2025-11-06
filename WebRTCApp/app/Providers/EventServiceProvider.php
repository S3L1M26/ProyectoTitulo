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
}
