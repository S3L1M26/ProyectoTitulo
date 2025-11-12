<?php

namespace App\Services;

use App\Exceptions\ZoomApiException;
use App\Exceptions\ZoomAuthException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZoomService
{
    private string $clientId;
    private string $clientSecret;
    private string $accountId;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.zoom.client_id', env('ZOOM_CLIENT_ID'));
        $this->clientSecret = (string) config('services.zoom.client_secret', env('ZOOM_CLIENT_SECRET'));
        $this->accountId = (string) config('services.zoom.account_id', env('ZOOM_ACCOUNT_ID'));
        $this->baseUrl = rtrim((string) config('services.zoom.base_url', env('ZOOM_API_BASE_URL', 'https://api.zoom.us/v2/')), '/');

        $missing = [];
        foreach ([
            'ZOOM_CLIENT_ID' => $this->clientId,
            'ZOOM_CLIENT_SECRET' => $this->clientSecret,
            'ZOOM_ACCOUNT_ID' => $this->accountId,
        ] as $key => $value) {
            if (empty($value)) {
                $missing[] = $key;
            }
        }

        if ($missing) {
            Log::channel('zoom')->error('ZoomService config incompleta', ['missing' => $missing]);
            throw ZoomAuthException::missingConfiguration($missing);
        }
    }

    /**
     * Obtener y cachear el access token Server-to-Server OAuth.
     */
    public function getAccessToken(): string
    {
        return Cache::remember('zoom_access_token', now()->addMinutes(55), function () {
            $basic = base64_encode($this->clientId . ':' . $this->clientSecret);

            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic '.$basic,
                ])
                ->retry(2, 200) // pequeños reintentos ante fallos transitorios
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ]);

            Log::channel('zoom')->info('Zoom OAuth token response', [
                'status' => $response->status(),
                'rate_limit' => [
                    'remaining' => $response->header('X-RateLimit-Remaining'),
                    'limit' => $response->header('X-RateLimit-Limit'),
                ],
            ]);

            if ($response->status() === 401) {
                throw ZoomAuthException::invalidCredentials();
            }

            if (!$response->successful()) {
                throw ZoomApiException::fromResponse($response);
            }

            $data = $response->json();
            if (empty($data['access_token'])) {
                throw new ZoomApiException('Respuesta OAuth inválida: access_token ausente');
            }

            return $data['access_token'];
        });
    }

    /**
     * Crear una reunión programada en Zoom.
     *
     * @param array $data [topic, start_time(local), duration, timezone, settings]
     * @return array{id:string|int, join_url:string, start_url:string, password:?string}
     * @throws ZoomApiException
     */
    public function crearReunion(array $data): array
    {
        $token = $this->getAccessToken();

        // Normalizar y convertir tiempo a ISO8601 en UTC si viene como local
        $startTime = $data['start_time'] ?? null; // puede ser Carbon|string
        $timezone = $data['timezone'] ?? config('app.timezone', 'UTC');
        if ($startTime instanceof \DateTimeInterface) {
            $startUtc = \Carbon\Carbon::instance($startTime)->setTimezone('UTC')->toIso8601String();
        } else {
            $startUtc = \Carbon\Carbon::parse((string) $startTime, $timezone)->setTimezone('UTC')->toIso8601String();
        }

        $payload = [
            'topic' => $data['topic'] ?? 'Mentoría',
            'type' => 2, // scheduled meeting
            'start_time' => $startUtc,
            'duration' => (int) ($data['duration'] ?? 60),
            'timezone' => 'UTC',
            'settings' => array_merge([
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'waiting_room' => true,
                'mute_upon_entry' => true,
                'approval_type' => 2, // no registration approval
            ], $data['settings'] ?? []),
        ];

        $http = Http::withToken($token)
            ->acceptJson()
            ->retry(3, 500, throw: false) // retry 3 with backoff; no throw para inspeccionar 429
            ->asJson();

        $response = $http->post($this->baseUrl.'/users/me/meetings', $payload);

        Log::channel('zoom')->info('Crear reunión Zoom', [
            'status' => $response->status(),
            'payload_topic' => $payload['topic'],
            'start_time' => $payload['start_time'],
        ]);

        if ($response->status() === 429) {
            throw ZoomApiException::rateLimited($response);
        }

        if (!$response->successful()) {
            throw ZoomApiException::fromResponse($response);
        }

        $body = $response->json();
        $required = ['id', 'join_url', 'start_url'];
        foreach ($required as $key) {
            if (empty($body[$key])) {
                throw new ZoomApiException('Respuesta inválida de Zoom: falta '.$key);
            }
        }

        return [
            'id' => $body['id'],
            'join_url' => $body['join_url'],
            'start_url' => $body['start_url'],
            'password' => $body['password'] ?? null,
        ];
    }

    /** Obtener detalles de una reunión. */
    public function obtenerReunion(string $meetingId): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->baseUrl.'/meetings/'.urlencode($meetingId));

        Log::channel('zoom')->info('Obtener reunión Zoom', ['status' => $response->status(), 'meeting_id' => $meetingId]);

        if ($response->status() === 404) {
            throw new ZoomApiException('Reunión no encontrada (404)');
        }
        if (!$response->successful()) {
            throw ZoomApiException::fromResponse($response);
        }
        return $response->json();
    }

    /** Cancelar una reunión. */
    public function cancelarReunion(string $meetingId): bool
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->acceptJson()
            ->delete($this->baseUrl.'/meetings/'.urlencode($meetingId));

        Log::channel('zoom')->info('Cancelar reunión Zoom', ['status' => $response->status(), 'meeting_id' => $meetingId]);

        if ($response->status() === 404) {
            throw new ZoomApiException('Reunión no encontrada (404)');
        }
        if ($response->status() === 204) {
            return true; // deleted
        }
        if (!$response->successful()) {
            throw ZoomApiException::fromResponse($response);
        }
        return true;
    }

    /** Actualizar una reunión. */
    public function actualizarReunion(string $meetingId, array $data): array
    {
        $token = $this->getAccessToken();

        $payload = $data;
        if (isset($data['start_time'])) {
            $tz = $data['timezone'] ?? config('app.timezone', 'UTC');
            $payload['start_time'] = \Carbon\Carbon::parse((string) $data['start_time'], $tz)
                ->setTimezone('UTC')->toIso8601String();
            $payload['timezone'] = 'UTC';
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->patch($this->baseUrl.'/meetings/'.urlencode($meetingId), $payload);

        Log::channel('zoom')->info('Actualizar reunión Zoom', [
            'status' => $response->status(),
            'meeting_id' => $meetingId,
        ]);

        if ($response->status() === 404) {
            throw new ZoomApiException('Reunión no encontrada (404)');
        }
        if (!$response->successful()) {
            throw ZoomApiException::fromResponse($response);
        }
        return $response->json();
    }
}
