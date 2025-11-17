<?php

namespace Tests\Feature;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Models\Mentor;
use App\Models\Aprendiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudMentoriaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_estudiante_puede_crear_solicitud_con_datos_validos(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create([
            'certificate_verified' => true,
        ]);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Me gustaría solicitar una mentoría sobre Laravel.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('solicitud_mentorias', [
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_estudiante_sin_certificado_no_puede_crear_solicitud(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create([
            'certificate_verified' => false,
        ]);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test',
        ]);

        $response->assertSessionHasErrors('certificado');
    }

    public function test_estudiante_sin_perfil_no_puede_crear_solicitud(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test',
        ]);

        $response->assertSessionHasErrors('perfil');
    }

    public function test_no_se_puede_crear_solicitud_a_mentor_sin_cv_verificado(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create([
            'certificate_verified' => true,
        ]);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => false,
            'disponible_ahora' => true,
        ]);

        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test',
        ]);

        $response->assertSessionHasErrors('mentor');
    }

    public function test_no_se_puede_crear_solicitud_a_mentor_no_disponible(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create([
            'certificate_verified' => true,
        ]);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => false,
        ]);

        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test',
        ]);

        $response->assertSessionHasErrors('disponibilidad');
    }

    public function test_no_duplicar_solicitudes_pendientes(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->for($estudiante)->create([
            'certificate_verified' => true,
        ]);
        
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        // Primera solicitud
        SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        // Intento de crear duplicada
        $response = $this->actingAs($estudiante)->post(route('solicitud-mentoria.store'), [
            'mentor_id' => $mentor->id,
            'mensaje' => 'Test',
        ]);

        $response->assertSessionHasErrors('solicitud');
    }

    public function test_mentor_puede_aceptar_solicitud(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
        ]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertEquals('aceptada', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->fecha_respuesta);
    }

    public function test_mentor_puede_rechazar_solicitud(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'cv_verified' => true,
        ]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($mentor)->post(route('mentor.solicitudes.reject', $solicitud->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertEquals('rechazada', $solicitud->fresh()->estado);
        $this->assertNotNull($solicitud->fresh()->fecha_respuesta);
    }

    public function test_mentor_no_puede_aceptar_solicitud_que_no_le_pertenece(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentorCorreo = User::factory()->create(['role' => 'mentor']);
        $mentorIncorrecto = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($mentorCorreo)->create(['cv_verified' => true]);
        Mentor::factory()->for($mentorIncorrecto)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentorCorreo->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($mentorIncorrecto)->post(route('mentor.solicitudes.accept', $solicitud->id));

        $response->assertSessionHasErrors('autorizacion');
        $this->assertEquals('pendiente', $solicitud->fresh()->estado);
    }

    public function test_mentor_no_puede_aceptar_solicitud_ya_procesada(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => true]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $response = $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        $response->assertSessionHasErrors('estado');
    }

    public function test_mentor_sin_cv_verificado_no_puede_aceptar(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($mentor)->create(['cv_verified' => false]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        $response->assertSessionHasErrors('cv');
    }

    public function test_mentor_sin_perfil_no_puede_aceptar(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($mentor)->post(route('mentor.solicitudes.accept', $solicitud->id));

        $response->assertSessionHasErrors('perfil');
    }
}
