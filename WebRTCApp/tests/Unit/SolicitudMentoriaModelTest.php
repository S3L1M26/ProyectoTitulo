<?php

namespace Tests\Unit;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Models\Mentor;
use App\Models\Aprendiz;
use App\Models\Mentoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudMentoriaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitud_pertenece_a_estudiante(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertTrue($solicitud->estudiante->is($estudiante));
    }

    public function test_solicitud_pertenece_a_mentor(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertTrue($solicitud->mentor->is($mentor));
    }

    public function test_solicitud_pertenece_a_aprendiz_profile(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create();
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertTrue($solicitud->aprendiz->is($aprendiz));
    }

    public function test_solicitud_pertenece_a_mentor_profile(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create();
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertTrue($solicitud->mentorProfile->is($mentorProfile));
    }

    public function test_scope_pendientes_filtra_solo_pendientes(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $pendientes = SolicitudMentoria::pendientes()->get();

        $this->assertCount(1, $pendientes);
        $this->assertEquals('pendiente', $pendientes->first()->estado);
    }

    public function test_scope_aceptadas_filtra_solo_aceptadas(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $aceptadas = SolicitudMentoria::aceptadas()->get();

        $this->assertCount(1, $aceptadas);
        $this->assertEquals('aceptada', $aceptadas->first()->estado);
    }

    public function test_scope_rechazadas_filtra_solo_rechazadas(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);
        
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $rechazadas = SolicitudMentoria::rechazadas()->get();

        $this->assertCount(1, $rechazadas);
        $this->assertEquals('rechazada', $rechazadas->first()->estado);
    }

    public function test_aceptar_metodo_cambia_estado_y_fecha(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
            'fecha_respuesta' => null,
        ]);

        $result = $solicitud->aceptar();

        $this->assertTrue($result);
        $this->assertEquals('aceptada', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->fecha_respuesta);
    }

    public function test_rechazar_metodo_cambia_estado_y_fecha(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
            'fecha_respuesta' => null,
        ]);

        $result = $solicitud->rechazar();

        $this->assertTrue($result);
        $this->assertEquals('rechazada', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->fecha_respuesta);
    }

    public function test_tiene_mentoria_programada_retorna_true_si_existe(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);
        
        Mentoria::factory()->create([
            'solicitud_id' => $solicitud->id,
        ]);

        $this->assertTrue($solicitud->tieneMentoriaProgramada());
    }

    public function test_tiene_mentoria_programada_retorna_false_si_no_existe(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertFalse($solicitud->tieneMentoriaProgramada());
    }

    public function test_esta_pendiente_retorna_true_si_pendiente(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $this->assertTrue($solicitud->estaPendiente());
    }

    public function test_esta_pendiente_retorna_false_si_aceptada(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $this->assertFalse($solicitud->estaPendiente());
    }

    public function test_tiene_mentoria_activa_con_mentor_retorna_true(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        Mentoria::factory()->create([
            'aprendiz_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'confirmada',
        ]);

        $result = SolicitudMentoria::tieneMentoriaActivaConMentor($estudiante->id, $mentor->id);

        $this->assertTrue($result);
    }

    public function test_tiene_mentoria_activa_con_mentor_retorna_false(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);

        $result = SolicitudMentoria::tieneMentoriaActivaConMentor($estudiante->id, $mentor->id);

        $this->assertFalse($result);
    }

    public function test_factory_crea_solicitud_con_estado_por_defecto(): void
    {
        $solicitud = SolicitudMentoria::factory()->create();

        $this->assertEquals('pendiente', $solicitud->estado);
    }

    public function test_factory_puede_crear_con_estado_especifico(): void
    {
        $solicitud = SolicitudMentoria::factory()->create(['estado' => 'aceptada']);

        $this->assertEquals('aceptada', $solicitud->estado);
    }

    public function test_soft_delete_funciona_correctamente(): void
    {
        $solicitud = SolicitudMentoria::factory()->create();
        
        $solicitud->delete();

        $this->assertSoftDeleted($solicitud);
    }

    public function test_fillable_attributes_son_asignables(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test mensaje',
            'estado' => 'pendiente',
        ]);

        $this->assertEquals('Test mensaje', $solicitud->mensaje);
        $this->assertEquals('pendiente', $solicitud->estado);
    }
}
