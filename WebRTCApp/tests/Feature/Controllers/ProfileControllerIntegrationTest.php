<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\AreaInteres;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ProfileControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deshabilitar middleware para poder hacer peticiones POST/PATCH en tests
        $this->withoutMiddleware();
    }

    #[Test]
    public function student_can_update_aprendiz_profile_with_complete_data()
    {
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'semestre' => 3,
            'objetivos' => 'Objetivos iniciales'
        ]);

        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);
        $react = AreaInteres::factory()->create(['nombre' => 'React']);

        $response = $this->actingAs($student)->patch(route('profile.update-aprendiz'), [
            'semestre' => 5,
            'objetivos' => 'Aprender desarrollo web full-stack con Laravel y React',
            'areas_interes' => [$php->id, $laravel->id, $react->id]
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');

        $aprendiz->refresh();
        $this->assertEquals(5, $aprendiz->semestre);
        $this->assertEquals('Aprender desarrollo web full-stack con Laravel y React', $aprendiz->objetivos);
        $this->assertCount(3, $aprendiz->areasInteres);
    }

    #[Test]
    public function student_profile_requires_at_least_one_area_of_interest()
    {
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create();

        $response = $this->actingAs($student)->patch(route('profile.update-aprendiz'), [
            'semestre' => 5,
            'objetivos' => 'Mis objetivos',
            'areas_interes' => []
        ]);

        $response->assertSessionHasErrors('areas_interes');
    }

    #[Test]
    public function mentor_can_update_profile_with_valid_data()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();

        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);

        $response = $this->actingAs($mentor)->patch(route('profile.update-mentor'), [
            'experiencia' => 'Tengo más de 10 años de experiencia como desarrollador senior full-stack, trabajando con diversas tecnologías web modernas',
            'biografia' => 'Soy un desarrollador apasionado por enseñar. He trabajado en múltiples proyectos empresariales y me encanta compartir mi conocimiento con estudiantes que quieren aprender programación web profesional',
            'años_experiencia' => 10,
            'disponibilidad' => 'Lunes a Viernes de 18:00 a 20:00',
            'disponibilidad_detalle' => 'Flexible para sesiones los fines de semana',
            'areas_especialidad' => [$php->id, $laravel->id]
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'mentor-profile-updated');

        $mentorProfile->refresh();
        $this->assertStringContainsString('desarrollador senior', $mentorProfile->experiencia);
        $this->assertEquals(10, $mentorProfile->años_experiencia);
        $this->assertCount(2, $mentorProfile->areasInteres);
    }

    #[Test]
    public function mentor_experiencia_must_have_minimum_50_characters()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();
        $php = AreaInteres::factory()->create();

        $response = $this->actingAs($mentor)->patch(route('profile.update-mentor'), [
            'experiencia' => 'Experiencia corta', // Menos de 50 caracteres
            'biografia' => 'Biografía suficientemente larga para cumplir con el requisito mínimo de 100 caracteres establecidos en la validación del formulario',
            'años_experiencia' => 5,
            'disponibilidad' => 'Lunes a Viernes',
            'areas_especialidad' => [$php->id]
        ]);

        $response->assertSessionHasErrors('experiencia');
    }

    #[Test]
    public function mentor_biografia_must_have_minimum_100_characters()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();
        $php = AreaInteres::factory()->create();

        $response = $this->actingAs($mentor)->patch(route('profile.update-mentor'), [
            'experiencia' => 'Tengo amplia experiencia como desarrollador full-stack trabajando en proyectos empresariales complejos',
            'biografia' => 'Biografía corta', // Menos de 100 caracteres
            'años_experiencia' => 5,
            'disponibilidad' => 'Lunes a Viernes',
            'areas_especialidad' => [$php->id]
        ]);

        $response->assertSessionHasErrors('biografia');
    }

    #[Test]
    public function mentor_must_have_at_least_one_area_of_specialty()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();

        $response = $this->actingAs($mentor)->patch(route('profile.update-mentor'), [
            'experiencia' => 'Tengo amplia experiencia como desarrollador full-stack trabajando en proyectos empresariales complejos',
            'biografia' => 'Soy un desarrollador apasionado por enseñar y compartir mis conocimientos con estudiantes que desean aprender programación',
            'años_experiencia' => 5,
            'disponibilidad' => 'Lunes a Viernes',
            'areas_especialidad' => []
        ]);

        $response->assertSessionHasErrors('areas_especialidad');
    }

    #[Test]
    public function mentor_can_toggle_availability_when_profile_is_complete()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'experiencia' => 'Tengo más de 10 años de experiencia como desarrollador senior full-stack trabajando con tecnologías web',
            'biografia' => 'Desarrollador apasionado por enseñar. He trabajado en múltiples proyectos y me gusta compartir mi conocimiento con estudiantes',
            'años_experiencia' => 10,
            'disponibilidad' => 'Lunes a Viernes',
            'disponible_ahora' => false
        ]);

        $php = AreaInteres::factory()->create();
        $mentorProfile->areasInteres()->attach($php->id);

        // Activar disponibilidad (usar POST según la ruta definida)
        $response = $this->actingAs($mentor)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status');

        $mentorProfile->refresh();
        $this->assertTrue($mentorProfile->disponible_ahora);
    }

    #[Test]
    public function mentor_cannot_be_available_with_incomplete_profile()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->incomplete()->for($mentor)->create([
            'disponible_ahora' => false
        ]);

        $response = $this->actingAs($mentor)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('disponibilidad');

        $mentorProfile->refresh();
        $this->assertFalse($mentorProfile->disponible_ahora);
    }

    #[Test]
    public function areas_interes_can_be_synced_for_student()
    {
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create();

        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);
        $react = AreaInteres::factory()->create(['nombre' => 'React']);

        // Agregar áreas iniciales
        $aprendiz->areasInteres()->attach([$php->id, $laravel->id]);
        $this->assertCount(2, $aprendiz->fresh()->areasInteres);

        // Actualizar con nuevas áreas (sync reemplaza)
        $response = $this->actingAs($student)->patch(route('profile.update-aprendiz'), [
            'semestre' => 5,
            'objetivos' => 'Objetivos actualizados',
            'areas_interes' => [$react->id, $laravel->id]
        ]);

        $response->assertRedirect();

        $aprendiz->refresh();
        $this->assertCount(2, $aprendiz->areasInteres);
        $this->assertFalse($aprendiz->areasInteres->contains('nombre', 'PHP'));
        $this->assertTrue($aprendiz->areasInteres->contains('nombre', 'React'));
        $this->assertTrue($aprendiz->areasInteres->contains('nombre', 'Laravel'));
    }

    #[Test]
    public function student_can_create_new_aprendiz_profile_if_not_exists()
    {
        // Test que verifica que se puede crear un perfil de aprendiz si no existe
        $student = User::factory()->student()->create();
        // Sin crear Aprendiz

        $php = AreaInteres::factory()->create();
        $laravel = AreaInteres::factory()->create();

        $response = $this->actingAs($student)->patch(route('profile.update-aprendiz'), [
            'semestre' => 3,
            'objetivos' => 'Aprender desarrollo web',
            'areas_interes' => [$php->id, $laravel->id]
        ]);

        $response->assertRedirect(route('profile.edit'));

        $student->refresh();
        $this->assertNotNull($student->aprendiz);
        $this->assertEquals(3, $student->aprendiz->semestre);
        $this->assertCount(2, $student->aprendiz->areasInteres);
    }
}
