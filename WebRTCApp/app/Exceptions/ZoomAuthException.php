<?php

namespace App\Exceptions;

use Exception;

class ZoomAuthException extends Exception
{
    public static function missingConfiguration(array $missing): self
    {
        return new self('Faltan variables de entorno para Zoom: '.implode(', ', $missing));
    }

    public static function invalidCredentials(): self
    {
        return new self('Credenciales de Zoom inválidas (401/invalid_client).');
    }
}