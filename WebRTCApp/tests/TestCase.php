<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $enablesMiddleware = true;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        if ($this->enablesMiddleware) {
            // Solo deshabilitar CSRF, mantener sesiones activas
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
    }
}
