<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class E2ETestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     * 
     * Detecta peticiones de tests E2E y cambia automáticamente
     * la conexión de base de datos a 'testing' (webrtc_testing)
     */
    public function boot(): void
    {
        // Detectar si la petición viene de tests E2E
        if (request()->hasHeader('X-E2E-Testing')) {
            // Cambiar la conexión de base de datos a testing
            $testingConnection = env('DB_TESTING_CONNECTION', 'testing');
            Config::set('database.default', $testingConnection);
            
            // Registrar en logs para debugging
            Log::debug('E2E Testing mode activated', [
                'connection' => $testingConnection,
                'database' => Config::get("database.connections.{$testingConnection}.database"),
            ]);
        }
    }
}
