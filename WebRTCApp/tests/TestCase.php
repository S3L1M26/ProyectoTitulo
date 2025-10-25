<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $enablesMiddleware = true;
    
    protected function setUp(): void
    {
        // Forzar el uso de la base de datos de testing ANTES de inicializar
        putenv('DB_DATABASE=webrtc_testing');
        $_ENV['DB_DATABASE'] = 'webrtc_testing';
        $_SERVER['DB_DATABASE'] = 'webrtc_testing';
        
        parent::setUp();
        
        if ($this->enablesMiddleware) {
            // Solo deshabilitar CSRF, mantener sesiones activas
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
    }
}
