<?php

namespace Tests\Feature;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Models\Mentor;
use App\Notifications\SolicitudMentoriaAceptada;
use App\Notifications\SolicitudMentoriaRechazada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SolicitudMentoriaNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Estudiante recibe notificación cuando mentor acepta
     */
    public function test_estudiante_recibe_notificacion_cuando_mentor_acepta(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Verificar que no hay notificaciones
        $this->assertCount(0, $estudiante->unreadNotifications()->get());

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Verificar que la notificación fue creada en la BD
        $notificaciones = $estudiante->unreadNotifications()
            ->where('type', 'App\Notifications\SolicitudMentoriaAceptada')
            ->get();
        
        $this->assertCount(1, $notificaciones);
    }

    /**
     * Test: Estudiante recibe notificación cuando mentor rechaza
     */
    public function test_estudiante_recibe_notificacion_cuando_mentor_rechaza(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor rechaza la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.reject', $solicitud->id));

        // Verificar que la notificación fue creada en la BD
        $notificaciones = $estudiante->unreadNotifications()
            ->where('type', 'App\Notifications\SolicitudMentoriaRechazada')
            ->get();
        
        $this->assertCount(1, $notificaciones);
    }

    /**
     * Test: Estudiante puede ver sus notificaciones no leídas
     */
    public function test_estudiante_puede_ver_sus_notificaciones(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Estudiante ve sus notificaciones
        $response = $this->actingAs($estudiante)->get(route('student.notifications'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->has('notificaciones')
                ->has('contadorNoLeidas')
        );
    }

    /**
     * Test: El contador de notificaciones no leídas es correcto
     */
    public function test_contador_de_notificaciones_no_leidas_es_correcto(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor1 = User::factory()->create(['role' => 'mentor']);
        $mentor2 = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($mentor1)->create(['cv_verified' => true]);
        Mentor::factory()->for($mentor2)->create(['cv_verified' => true]);
        
        // Crear dos solicitudes
        $solicitud1 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor1->id,
            'estado' => 'pendiente',
        ]);
        
        $solicitud2 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor2->id,
            'estado' => 'pendiente',
        ]);

        // Ambos mentores aceptan
        $this->actingAs($mentor1)->post(route('mentor.solicitudes.accept', $solicitud1->id));
        $this->actingAs($mentor2)->post(route('mentor.solicitudes.accept', $solicitud2->id));

        // Ver notificaciones
        $response = $this->actingAs($estudiante)->get(route('student.notifications'));
        
        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->where('contadorNoLeidas', fn ($count) => $count >= 2)
        );
    }

    /**
     * Test: Estudiante puede marcar una notificación como leída
     */
    public function test_estudiante_puede_marcar_notificacion_como_leida(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Obtener la notificación
        $notification = $estudiante->unreadNotifications()->first();
        $this->assertNotNull($notification);
        
        // Marcar como leída
        $response = $this->actingAs($estudiante)->post(
            route('student.notifications.read', $notification->id)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que está marcada como leída
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test: Estudiante puede marcar todas las notificaciones como leídas
     */
    public function test_estudiante_puede_marcar_todas_notificaciones_como_leidas(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor1 = User::factory()->create(['role' => 'mentor']);
        $mentor2 = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($mentor1)->create(['cv_verified' => true]);
        Mentor::factory()->for($mentor2)->create(['cv_verified' => true]);
        
        // Crear dos solicitudes
        $solicitud1 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor1->id,
            'estado' => 'pendiente',
        ]);
        
        $solicitud2 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor2->id,
            'estado' => 'pendiente',
        ]);

        // Ambos mentores aceptan
        $this->actingAs($mentor1)->post(route('mentor.solicitudes.accept', $solicitud1->id));
        $this->actingAs($mentor2)->post(route('mentor.solicitudes.accept', $solicitud2->id));

        // Verificar que hay notificaciones no leídas
        $unreadBefore = $estudiante->unreadNotifications()->count();
        $this->assertGreaterThanOrEqual(2, $unreadBefore);

        // Marcar todas como leídas
        $response = $this->actingAs($estudiante)->post(route('student.notifications.read-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que todas están marcadas como leídas
        $unreadAfter = $estudiante->unreadNotifications()->count();
        $this->assertEquals(0, $unreadAfter);
    }

    /**
     * Test: La notificación contiene los datos correctos de la solicitud
     */
    public function test_notificacion_contiene_datos_correctos(): void
    {
        $estudiante = User::factory()->create([
            'role' => 'student',
            'name' => 'Juan Pérez',
        ]);
        $mentor = User::factory()->create([
            'role' => 'mentor',
            'name' => 'Dr. García López',
        ]);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Verificar que la notificación contiene los datos correctos
        $notificacion = $estudiante->unreadNotifications()
            ->where('type', 'App\Notifications\SolicitudMentoriaAceptada')
            ->first();
        
        $this->assertNotNull($notificacion);
        $this->assertArrayHasKey('solicitud_id', $notificacion->data);
        $this->assertEquals($solicitud->id, $notificacion->data['solicitud_id']);
    }

    /**
     * Test: Solo se muestran notificaciones de SolicitudMentoria del estudiante
     */
    public function test_solo_notificaciones_propias_del_estudiante(): void
    {
        $estudiante1 = User::factory()->create(['role' => 'student']);
        $estudiante2 = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        // Solicitud para estudiante1
        $solicitud1 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante1->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);
        
        // Solicitud para estudiante2
        $solicitud2 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante2->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta ambas
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud1->id));
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud2->id));

        // Estudiante1 ve solo sus notificaciones
        $response = $this->actingAs($estudiante1)->get(route('student.notifications'));
        
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->has('notificaciones')
                ->has('notificaciones.0')
                ->etc()
        );

        // Todas las notificaciones deben ser del estudiante1
        $estudiante1->refresh();
        $notificaciones = $estudiante1->unreadNotifications;
        
        foreach ($notificaciones as $notif) {
            $this->assertArrayHasKey('data', $notif->toArray());
            $solicitud_id = $notif->data['solicitud_id'];
            
            // Verificar que la solicitud pertenece a estudiante1
            $solicitud = SolicitudMentoria::find($solicitud_id);
            $this->assertEquals($estudiante1->id, $solicitud->estudiante_id);
        }
    }

    /**
     * Test: Estudiante no puede marcar notificación de otro estudiante
     */
    public function test_estudiante_no_puede_marcar_notificacion_ajena(): void
    {
        $estudiante1 = User::factory()->create(['role' => 'student']);
        $estudiante2 = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante1->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Obtener notificación del estudiante 1
        $notification = $estudiante1->unreadNotifications()->first();
        $this->assertNotNull($notification);

        // Estudiante 2 intenta marcar la notificación de estudiante 1
        $response = $this->actingAs($estudiante2)->post(
            route('student.notifications.read', $notification->id)
        );

        // Debe fallar (ValidationException)
        $response->assertSessionHasErrors();
    }

    /**
     * Test: Las notificaciones están ordenadas por fecha descendente
     */
    public function test_notificaciones_ordenadas_descendente(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor1 = User::factory()->create(['role' => 'mentor']);
        $mentor2 = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($mentor1)->create(['cv_verified' => true]);
        Mentor::factory()->for($mentor2)->create(['cv_verified' => true]);
        
        // Crear dos solicitudes
        $solicitud1 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor1->id,
            'estado' => 'pendiente',
        ]);
        
        // Esperar un bit para que haya diferencia de tiempo
        sleep(1);
        
        $solicitud2 = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor2->id,
            'estado' => 'pendiente',
        ]);

        // Mentor 1 acepta primero
        $this->actingAs($mentor1)->post(route('mentor.solicitudes.accept', $solicitud1->id));
        
        // Mentor 2 acepta después
        $this->actingAs($mentor2)->post(route('mentor.solicitudes.accept', $solicitud2->id));

        // Ver notificaciones
        $response = $this->actingAs($estudiante)->get(route('student.notifications'));
        
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->has('notificaciones', 2)
        );

        // Verificar orden directamente desde la base de datos
        $estudiante->refresh();
        $notificaciones = $estudiante->unreadNotifications->toArray();

        // Debe haber 2 notificaciones
        $this->assertCount(2, $notificaciones);
        
        // La primera debe ser más reciente que la segunda
        $fecha1 = new \DateTime($notificaciones[0]['created_at']);
        $fecha2 = new \DateTime($notificaciones[1]['created_at']);
        
        $this->assertGreaterThanOrEqual($fecha2, $fecha1);
    }

    /**
     * Test: El estado de la notificación refleja si fue leída o no
     */
    public function test_estado_lectura_de_notificacion(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Mentor acepta la solicitud
        $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        // Ver notificaciones (sin leer)
        $response = $this->actingAs($estudiante)->get(route('student.notifications'));
        
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->has('notificaciones', 1)
                ->has('notificaciones.0', fn (Assert $prop) =>
                    $prop->where('read_at', null)
                        ->etc()
                )
        );
        
        // La notificación debe no tener read_at
        $estudiante->refresh();
        $notif = $estudiante->unreadNotifications->first();
        $this->assertNull($notif->read_at);

        // Marcar como leída
        $notification = $estudiante->unreadNotifications()->first();
        $this->actingAs($estudiante)->post(
            route('student.notifications.read', $notification->id)
        );

        // Ver notificaciones nuevamente
        $response = $this->actingAs($estudiante)->get(route('student.notifications'));
        
        $response->assertInertia(fn (Assert $page) => 
            $page->component('Student/Notifications/Index')
                ->where('notificaciones', fn ($notifs) => count($notifs) === 0)
        );
    }
}
