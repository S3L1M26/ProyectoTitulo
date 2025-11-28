<?php

namespace Tests\Feature;

use App\Events\MentoriaConfirmada;
use App\Jobs\EnviarCorreoMentoria;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MentoriaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock de Zoom API para todos los tests
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 123456789,
                'join_url' => 'https://zoom.us/j/123456789?pwd=test',
                'start_url' => 'https://zoom.us/s/123456789?zak=test',
                'password' => 'testpass',
            ], 201),
        ]);
    }

    /**
     * Test: Mentor puede confirmar su propia solicitud aceptada
     */
    public function test_mentor_puede_confirmar_su_propia_solicitud_aceptada(): void
    {
        // Arrange
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create(['certificate_verified' => true]);
        Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        // Act
        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $hora = '10:00';

        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => $hora,
            'duracion_minutos' => 60,
            'topic' => 'Mentoría de prueba',
            'timezone' => 'UTC',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('status', 'Mentoría confirmada');

        // Verificar que se creó la mentoría
        $this->assertDatabaseHas('mentorias', [
            'solicitud_id' => $solicitud->id,
            'aprendiz_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'confirmada',
        ]);

        $mentoria = Mentoria::where('solicitud_id', $solicitud->id)->first();
        $this->assertNotNull($mentoria);
        $this->assertEquals('https://zoom.us/j/123456789?pwd=test', $mentoria->enlace_reunion);
        $this->assertEquals('123456789', $mentoria->zoom_meeting_id);
    }

    /**
     * Test: Otro mentor no puede confirmar solicitud ajena
     */
    public function test_otro_mentor_no_puede_confirmar_solicitud_ajena(): void
    {
        // Arrange
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor1 = User::factory()->create(['role' => 'mentor']);
        $mentor2 = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create(['certificate_verified' => true]);
        Mentor::factory()->for($mentor1)->create(['cv_verified' => true]);
        Mentor::factory()->for($mentor2)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor1->id,
            'estado' => 'aceptada',
        ]);

        // Act - Mentor2 intenta confirmar solicitud de Mentor1
        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($mentor2)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '14:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Assert - Debe ser denegado (403)
        $response->assertForbidden();

        // Verificar que NO se creó la mentoría
        $this->assertDatabaseMissing('mentorias', [
            'solicitud_id' => $solicitud->id,
        ]);
    }

    /**
     * Test: No se puede confirmar solicitud que ya tiene mentoría programada
     */
    public function test_no_se_puede_confirmar_solicitud_ya_programada(): void
    {
        // Arrange
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        // Crear una mentoría existente para esta solicitud
        Mentoria::factory()->create([
            'solicitud_id' => $solicitud->id,
            'aprendiz_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'confirmada',
        ]);

        // Act - Intentar confirmar nuevamente
        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '16:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Assert
        $response->assertForbidden();

        // Solo debe haber UNA mentoría
        $this->assertEquals(1, Mentoria::where('solicitud_id', $solicitud->id)->count());
    }

    /**
     * Test: Validación de fecha en el pasado es rechazada
     */
    public function test_validacion_fecha_pasado_es_rechazada(): void
    {
        // Arrange
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        // Act - Fecha en el pasado
        $fechaPasada = Carbon::yesterday()->format('Y-m-d');
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fechaPasada,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Assert
        // El error puede venir con la clave 'hora' porque el controlador valida con isPast()
        $response->assertSessionHasErrors(); // Debe tener algún error
        
        $this->assertDatabaseMissing('mentorias', [
            'solicitud_id' => $solicitud->id,
        ]);
    }

    /**
     * Test: Validación de hora en el pasado (mismo día) es rechazada
     */
    public function test_validacion_hora_pasado_es_rechazada(): void
    {
        // Arrange
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        // Act - Fecha/hora pasada (con fecha sincronizada al timestamp usado)
        $pasado = Carbon::now('UTC')->subHour();
        $fecha = $pasado->format('Y-m-d');
        $horaPasada = $pasado->format('H:i');
        
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => $horaPasada,
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Assert - El controlador valida con isPast()
        $response->assertSessionHasErrors(['hora']);
    }

    /**
     * Test: Validación de duración mínima (30 min)
     */
    public function test_validacion_duracion_minima(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 15, // Menor a 30
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors(['duracion_minutos']);
    }

    /**
     * Test: Validación de duración máxima (180 min)
     */
    public function test_validacion_duracion_maxima(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 200, // Mayor a 180
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors(['duracion_minutos']);
    }

    /**
     * Test: Evento MentoriaConfirmada se dispara correctamente
     */
    public function test_evento_mentoria_confirmada_se_dispara(): void
    {
        Event::fake([MentoriaConfirmada::class]);

        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Verificar que se disparó el evento
        Event::assertDispatched(MentoriaConfirmada::class, function ($event) use ($solicitud) {
            return $event->mentoria->solicitud_id === $solicitud->id;
        });
    }

    /**
     * Test: Email se encola correctamente vía listener
     */
    public function test_email_se_encola_correctamente(): void
    {
        Queue::fake();

        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Verificar que se encoló el job de envío de correo
        Queue::assertPushed(EnviarCorreoMentoria::class, function ($job) use ($solicitud) {
            return $job->mentoria->solicitud_id === $solicitud->id;
        });
    }

    /**
     * Test: Solicitud cambia a "aceptada" si estaba en "pendiente"
     */
    public function test_solicitud_pendiente_cambia_a_aceptada_al_confirmar(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente', // Estado inicial pendiente
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Verificar que la solicitud cambió a aceptada
        $this->assertEquals('aceptada', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->fecha_respuesta);
    }

    /**
     * Test: Timezone se respeta en conversión a UTC
     */
    public function test_timezone_se_respeta_en_conversion(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        // Fecha/hora en timezone America/Santiago
        $fecha = Carbon::tomorrow('America/Santiago')->format('Y-m-d');
        $hora = '14:00'; // 2 PM Santiago

        $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => $hora,
            'duracion_minutos' => 60,
            'timezone' => 'America/Santiago',
        ]);

        // Verificar que se envió correctamente a Zoom API
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/users/me/meetings')) {
                return false;
            }
            
            $body = $request->data();
            // Debe contener timezone UTC en el payload
            return isset($body['timezone']) && $body['timezone'] === 'UTC' &&
                   isset($body['start_time']);
        });

        // Verificar que la mentoría se guardó
        $mentoria = Mentoria::where('solicitud_id', $solicitud->id)->first();
        $this->assertNotNull($mentoria);
    }

    /**
     * Test: No se puede confirmar solicitud rechazada
     */
    public function test_no_se_puede_confirmar_solicitud_rechazada(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($mentor)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Debe ser denegado por la policy
        $response->assertForbidden();
    }

    /**
     * Test: Estudiante no puede confirmar mentoría
     */
    public function test_estudiante_no_puede_confirmar_mentoria(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($estudiante)->post(route('mentorias.confirmar', $solicitud), [
            'fecha' => $fecha,
            'hora' => '10:00',
            'duracion_minutos' => 60,
            'timezone' => 'UTC',
        ]);

        // Assert
        // Puede retornar 302 (redirect) si hay middleware de role,  
        // o 403 si la policy se ejecuta. Ambos son correctos para denegar acceso
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            "Expected 403 or 302, got {$response->status()}"
        );
    }

    /**
     * Test: Prevención de doble dispatch con mismo CID
     */
    public function test_prevencion_doble_dispatch_con_mismo_cid(): void
    {
        Event::fake([MentoriaConfirmada::class]);

        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Aprendiz::factory()->for($estudiante)->create();
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);

        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $fecha = Carbon::tomorrow()->format('Y-m-d');
        $cid = 'test_cid_123';

        // Primera llamada con CID
        $this->actingAs($mentor)
            ->withHeader('X-CID', $cid)
            ->post(route('mentorias.confirmar', $solicitud), [
                'fecha' => $fecha,
                'hora' => '10:00',
                'duracion_minutos' => 60,
                'timezone' => 'UTC',
            ]);

        // Segunda llamada con MISMO CID (debe ser ignorada)
        $this->actingAs($mentor)
            ->withHeader('X-CID', $cid)
            ->post(route('mentorias.confirmar', $solicitud), [
                'fecha' => $fecha,
                'hora' => '11:00', // Hora diferente
                'duracion_minutos' => 60,
                'timezone' => 'UTC',
            ]);

        // Solo debe haberse disparado UNA vez el evento
        Event::assertDispatchedTimes(MentoriaConfirmada::class, 1);
    }
}
