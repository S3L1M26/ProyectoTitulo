<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Programación de tareas automáticas
 */
Schedule::command('mentorias:enviar-recordatorios')
    ->dailyAt('09:00')
    ->timezone('America/Santiago')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('✅ Recordatorios de mentorías enviados exitosamente');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('❌ Error al enviar recordatorios de mentorías');
    });
