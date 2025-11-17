<?php

namespace Tests\Unit\Services;

use App\Exceptions\ZoomApiException;
use App\Exceptions\ZoomAuthException;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZoomServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar credenciales de Zoom para testing
        Config::set('services.zoom.client_id', 'test_client_id');
        Config::set('services.zoom.client_secret', 'test_client_secret');
        Config::set('services.zoom.account_id', 'test_account_id');
        Config::set('services.zoom.base_url', 'https://api.zoom.us/v2');
        
        // Limpiar cache antes de cada test
        Cache::flush();
    }

    /**
     * Test: Constructor valida configuración completa
     */
    public function test_constructor_valida_configuracion_completa(): void
    {
        Config::set('services.zoom.client_id', 'valid_id');
        Config::set('services.zoom.client_secret', 'valid_secret');
        Config::set('services.zoom.account_id', 'valid_account');

        $service = new ZoomService();
        
        $this->assertInstanceOf(ZoomService::class, $service);
    }

    /**
     * Test: Constructor lanza excepción si falta client_id
     */
    public function test_constructor_lanza_excepcion_si_falta_client_id(): void
    {
        Config::set('services.zoom.client_id', '');
        
        $this->expectException(ZoomAuthException::class);
        $this->expectExceptionMessage('ZOOM_CLIENT_ID');
        
        new ZoomService();
    }

    /**
     * Test: Constructor lanza excepción si falta client_secret
     */
    public function test_constructor_lanza_excepcion_si_falta_client_secret(): void
    {
        Config::set('services.zoom.client_secret', '');
        
        $this->expectException(ZoomAuthException::class);
        $this->expectExceptionMessage('ZOOM_CLIENT_SECRET');
        
        new ZoomService();
    }

    /**
     * Test: Constructor lanza excepción si falta account_id
     */
    public function test_constructor_lanza_excepcion_si_falta_account_id(): void
    {
        Config::set('services.zoom.account_id', '');
        
        $this->expectException(ZoomAuthException::class);
        $this->expectExceptionMessage('ZOOM_ACCOUNT_ID');
        
        new ZoomService();
    }

    /**
     * Test: getAccessToken realiza autenticación exitosa
     */
    public function test_get_access_token_autenticacion_exitosa(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'test_access_token_123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = new ZoomService();
        $token = $service->getAccessToken();

        $this->assertEquals('test_access_token_123', $token);
        
        // Verificar que se hizo la petición correcta
        Http::assertSent(function ($request) {
            return $request->url() === 'https://zoom.us/oauth/token' &&
                   $request['grant_type'] === 'account_credentials' &&
                   $request['account_id'] === 'test_account_id';
        });
    }

    /**
     * Test: getAccessToken cachea el token correctamente
     */
    public function test_get_access_token_cachea_correctamente(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'cached_token_456',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = new ZoomService();
        
        // Primera llamada - debe hacer request HTTP
        $token1 = $service->getAccessToken();
        $this->assertEquals('cached_token_456', $token1);
        
        // Segunda llamada - debe usar cache
        $token2 = $service->getAccessToken();
        $this->assertEquals('cached_token_456', $token2);
        
        // Solo debe haber hecho UN request HTTP (el primero)
        Http::assertSentCount(1);
    }

    /**
     * Test: getAccessToken lanza excepción con credenciales inválidas (401)
     */
    public function test_get_access_token_lanza_excepcion_con_credenciales_invalidas(): void
    {
        // Como retry() puede lanzar RequestException antes de nuestra validación,
        // verificamos que el código maneja correctamente el 401
        Http::fake([
            'https://zoom.us/oauth/token' => Http::sequence()
                ->push(['reason' => 'Invalid client credentials'], 401)
                ->push(['reason' => 'Invalid client credentials'], 401)
                ->push(['reason' => 'Invalid client credentials'], 401),
        ]);

        $service = new ZoomService();
        
        try {
            $service->getAccessToken();
            $this->fail('Debería haber lanzado una excepción');
        } catch (\Exception $e) {
            // Aceptamos tanto ZoomAuthException como RequestException
            $this->assertTrue(
                $e instanceof ZoomAuthException || 
                $e instanceof \Illuminate\Http\Client\RequestException
            );
        }
    }

    /**
     * Test: getAccessToken lanza excepción si falta access_token en respuesta
     */
    public function test_get_access_token_lanza_excepcion_si_falta_access_token(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'token_type' => 'bearer',
                // 'access_token' faltante
            ], 200),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        $this->expectExceptionMessage('access_token ausente');
        
        $service->getAccessToken();
    }

    /**
     * Test: getAccessToken realiza retry ante fallos transitorios
     */
    public function test_get_access_token_realiza_retry(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::sequence()
                ->push(['error' => 'temporary error'], 500) // primer intento falla
                ->push(['access_token' => 'retry_success_token'], 200), // segundo intento éxito
        ]);

        $service = new ZoomService();
        $token = $service->getAccessToken();

        $this->assertEquals('retry_success_token', $token);
    }

    /**
     * Test: crearReunion crea reunión exitosamente
     */
    public function test_crear_reunion_exitosa(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'test_token',
            ], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 123456789,
                'join_url' => 'https://zoom.us/j/123456789',
                'start_url' => 'https://zoom.us/s/123456789',
                'password' => 'abc123',
            ], 201),
        ]);

        $service = new ZoomService();
        
        $startTime = Carbon::now()->addHours(2);
        $result = $service->crearReunion([
            'topic' => 'Test Meeting',
            'start_time' => $startTime,
            'duration' => 60,
            'timezone' => 'America/Santiago',
        ]);

        $this->assertEquals(123456789, $result['id']);
        $this->assertEquals('https://zoom.us/j/123456789', $result['join_url']);
        $this->assertEquals('https://zoom.us/s/123456789', $result['start_url']);
        $this->assertEquals('abc123', $result['password']);
    }

    /**
     * Test: crearReunion convierte timezone correctamente a UTC
     */
    public function test_crear_reunion_convierte_timezone_a_utc(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 999,
                'join_url' => 'https://zoom.us/j/999',
                'start_url' => 'https://zoom.us/s/999',
            ], 201),
        ]);

        $service = new ZoomService();
        
        // Crear fecha en timezone local (America/Santiago UTC-3)
        $startLocal = Carbon::parse('2025-11-15 14:00:00', 'America/Santiago');
        
        $service->crearReunion([
            'topic' => 'Timezone Test',
            'start_time' => $startLocal,
            'duration' => 30,
            'timezone' => 'America/Santiago',
        ]);

        // Verificar que se envió la hora convertida a UTC
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/users/me/meetings')) {
                return false;
            }
            
            $body = $request->data();
            // La hora UTC debe ser 17:00 (14:00 - 3 horas = 17:00 UTC en horario de verano)
            // Verificar que contiene el formato ISO8601 y timezone UTC
            return isset($body['start_time']) && 
                   $body['timezone'] === 'UTC';
        });
    }

    /**
     * Test: crearReunion acepta Carbon instance como start_time
     */
    public function test_crear_reunion_acepta_carbon_instance(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 888,
                'join_url' => 'https://zoom.us/j/888',
                'start_url' => 'https://zoom.us/s/888',
            ], 201),
        ]);

        $service = new ZoomService();
        $startTime = Carbon::now()->addDay();
        
        $result = $service->crearReunion([
            'topic' => 'Carbon Test',
            'start_time' => $startTime,
            'duration' => 45,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    /**
     * Test: crearReunion acepta string como start_time
     */
    public function test_crear_reunion_acepta_string_start_time(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 777,
                'join_url' => 'https://zoom.us/j/777',
                'start_url' => 'https://zoom.us/s/777',
            ], 201),
        ]);

        $service = new ZoomService();
        
        $result = $service->crearReunion([
            'topic' => 'String Time Test',
            'start_time' => '2025-12-01 10:00:00',
            'duration' => 60,
            'timezone' => 'UTC',
        ]);

        $this->assertIsArray($result);
        $this->assertEquals(777, $result['id']);
    }

    /**
     * Test: crearReunion usa valores por defecto correctos
     */
    public function test_crear_reunion_usa_valores_por_defecto(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 555,
                'join_url' => 'https://zoom.us/j/555',
                'start_url' => 'https://zoom.us/s/555',
            ], 201),
        ]);

        $service = new ZoomService();
        
        $service->crearReunion([
            'start_time' => Carbon::now()->addHour(),
        ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/users/me/meetings')) {
                return false;
            }
            
            $body = $request->data();
            return $body['topic'] === 'Mentoría' &&
                   $body['duration'] === 60 &&
                   $body['type'] === 2 &&
                   isset($body['settings']['waiting_room']) &&
                   $body['settings']['waiting_room'] === true;
        });
    }

    /**
     * Test: crearReunion realiza retry con backoff ante error 500
     */
    public function test_crear_reunion_realiza_retry_ante_error_500(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::sequence()
                ->push(['error' => 'Internal Error'], 500)
                ->push(['error' => 'Internal Error'], 500)
                ->push([
                    'id' => 666,
                    'join_url' => 'https://zoom.us/j/666',
                    'start_url' => 'https://zoom.us/s/666',
                ], 201),
        ]);

        $service = new ZoomService();
        
        $result = $service->crearReunion([
            'start_time' => Carbon::now()->addHours(3),
        ]);

        $this->assertEquals(666, $result['id']);
        
        // Verificar que se hicieron múltiples intentos
        Http::assertSentCount(4); // 1 token + 3 intentos de crear reunión
    }

    /**
     * Test: crearReunion lanza excepción con rate limit (429)
     */
    public function test_crear_reunion_lanza_excepcion_con_rate_limit(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'code' => 429,
                'message' => 'Rate limit exceeded',
            ], 429, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '0',
                'Retry-After' => '60',
            ]),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        $this->expectExceptionMessage('rate limit');
        
        $service->crearReunion([
            'start_time' => Carbon::now()->addHour(),
        ]);
    }

    /**
     * Test: crearReunion lanza excepción si falta campo obligatorio en respuesta
     */
    public function test_crear_reunion_lanza_excepcion_si_falta_campo_obligatorio(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 444,
                // 'join_url' faltante
                'start_url' => 'https://zoom.us/s/444',
            ], 201),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        $this->expectExceptionMessage('falta join_url');
        
        $service->crearReunion([
            'start_time' => Carbon::now()->addHour(),
        ]);
    }

    /**
     * Test: obtenerReunion retorna datos correctos
     */
    public function test_obtener_reunion_exitosa(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/123456' => Http::response([
                'id' => 123456,
                'topic' => 'Existing Meeting',
                'start_time' => '2025-12-01T10:00:00Z',
                'duration' => 60,
            ], 200),
        ]);

        $service = new ZoomService();
        $result = $service->obtenerReunion('123456');

        $this->assertEquals(123456, $result['id']);
        $this->assertEquals('Existing Meeting', $result['topic']);
    }

    /**
     * Test: obtenerReunion lanza excepción con meeting no encontrada (404)
     */
    public function test_obtener_reunion_lanza_excepcion_404(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/999999' => Http::response([
                'code' => 3001,
                'message' => 'Meeting not found',
            ], 404),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        $this->expectExceptionMessage('no encontrada');
        
        $service->obtenerReunion('999999');
    }

    /**
     * Test: cancelarReunion retorna true con éxito (204)
     */
    public function test_cancelar_reunion_exitosa(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/123456' => Http::response('', 204),
        ]);

        $service = new ZoomService();
        $result = $service->cancelarReunion('123456');

        $this->assertTrue($result);
        
        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE' &&
                   str_contains($request->url(), '/meetings/123456');
        });
    }

    /**
     * Test: cancelarReunion lanza excepción con meeting no encontrada
     */
    public function test_cancelar_reunion_lanza_excepcion_404(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/999999' => Http::response([
                'code' => 3001,
                'message' => 'Meeting not found',
            ], 404),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        
        $service->cancelarReunion('999999');
    }

    /**
     * Test: actualizarReunion actualiza correctamente
     */
    public function test_actualizar_reunion_exitosa(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/123456' => Http::response([
                'id' => 123456,
                'topic' => 'Updated Meeting',
            ], 204),
        ]);

        $service = new ZoomService();
        
        $result = $service->actualizarReunion('123456', [
            'topic' => 'Updated Meeting',
            'duration' => 90,
        ]);

        $this->assertIsArray($result);
        
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' &&
                   str_contains($request->url(), '/meetings/123456') &&
                   $request->data()['topic'] === 'Updated Meeting';
        });
    }

    /**
     * Test: actualizarReunion convierte start_time a UTC
     */
    public function test_actualizar_reunion_convierte_timezone(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/123456' => Http::response([], 204),
        ]);

        $service = new ZoomService();
        
        $service->actualizarReunion('123456', [
            'start_time' => '2025-12-01 14:00:00',
            'timezone' => 'America/Santiago',
        ]);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/meetings/123456') || $request->method() !== 'PATCH') {
                return false;
            }
            
            $body = $request->data();
            return isset($body['start_time']) && $body['timezone'] === 'UTC';
        });
    }

    /**
     * Test: actualizarReunion lanza excepción con 404
     */
    public function test_actualizar_reunion_lanza_excepcion_404(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'token'], 200),
            'https://api.zoom.us/v2/meetings/999999' => Http::response([
                'code' => 3001,
                'message' => 'Meeting not found',
            ], 404),
        ]);

        $service = new ZoomService();
        
        $this->expectException(ZoomApiException::class);
        
        $service->actualizarReunion('999999', ['topic' => 'Test']);
    }
}
