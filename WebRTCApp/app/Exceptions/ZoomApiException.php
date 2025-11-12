<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class ZoomApiException extends Exception
{
    public function __construct(string $message, protected ?Response $response = null)
    {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $body = $response->json();
        $detail = $body['message'] ?? $body['error'] ?? 'Error desconocido en Zoom API';
        return new self("Zoom API error ({$status}): {$detail}", $response);
    }

    public static function rateLimited(Response $response): self
    {
        $retryAfter = $response->header('Retry-After', 'desconocido');
        return new self('Zoom API rate limit alcanzado. Retry-After: '.$retryAfter, $response);
    }

    public function status(): ?int
    {
        return $this->response?->status();
    }
}