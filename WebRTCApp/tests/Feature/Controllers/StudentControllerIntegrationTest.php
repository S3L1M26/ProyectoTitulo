<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\AreaInteres;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class StudentControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function student_dashboard_returns_mentor_suggestions_based_on_shared_areas()
    {
        // Crear áreas de interés
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);
        $react = AreaInteres::factory()->create(['nombre' => 'React']);

        // Crear estudiante con áreas PHP y Laravel y certificado verificado
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);
        $aprendiz->areasInteres()->attach([$php->id, $laravel->id]);

        // Crear mentores
        // Mentor 1: Comparte PHP y Laravel (debe aparecer)
        $mentor1 = User::factory()->mentor()->create(['name' => 'Mentor PHP Laravel']);
        $mentorProfile1 = Mentor::factory()->available()->highRating()->for($mentor1)->create([
            'calificacionPromedio' => 4.8
        ]);
        $mentorProfile1->areasInteres()->attach([$php->id, $laravel->id]);

        // Mentor 2: Solo comparte PHP (debe aparecer)
        $mentor2 = User::factory()->mentor()->create(['name' => 'Mentor PHP']);
        $mentorProfile2 = Mentor::factory()->available()->for($mentor2)->create([
            'calificacionPromedio' => 4.5
        ]);
        $mentorProfile2->areasInteres()->attach([$php->id]);

        // Mentor 3: No disponible pero comparte áreas (NO debe aparecer)
        $mentor3 = User::factory()->mentor()->create(['name' => 'Mentor No Disponible']);
        $mentorProfile3 = Mentor::factory()->unavailable()->for($mentor3)->create();
        $mentorProfile3->areasInteres()->attach([$php->id, $laravel->id]);

        // Mentor 4: Disponible pero sin áreas en común (NO debe aparecer)
        $mentor4 = User::factory()->mentor()->create(['name' => 'Mentor React']);
        $mentorProfile4 = Mentor::factory()->available()->for($mentor4)->create();
        $mentorProfile4->areasInteres()->attach([$react->id]);

        // Actuar como el estudiante y visitar el dashboard
        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        
        // Verificar que solo aparecen los mentores correctos
        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];
        
        // Ahora las sugerencias son un array directo de mentores
        $this->assertCount(2, $suggestions);
        $this->assertEquals('Mentor PHP Laravel', $suggestions[0]['name']);
        $this->assertEquals('Mentor PHP', $suggestions[1]['name']);
    }

    #[Test]
    public function mentor_suggestions_are_ordered_by_rating_descending()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);
        $aprendiz->areasInteres()->attach([$php->id]);

        // Crear mentores con diferentes calificaciones
        $mentor1 = User::factory()->mentor()->create(['name' => 'Mentor Rating 3.5']);
        $mentorProfile1 = Mentor::factory()->available()->for($mentor1)->create([
            'calificacionPromedio' => 3.5
        ]);
        $mentorProfile1->areasInteres()->attach([$php->id]);

        $mentor2 = User::factory()->mentor()->create(['name' => 'Mentor Rating 4.9']);
        $mentorProfile2 = Mentor::factory()->available()->for($mentor2)->create([
            'calificacionPromedio' => 4.9
        ]);
        $mentorProfile2->areasInteres()->attach([$php->id]);

        $mentor3 = User::factory()->mentor()->create(['name' => 'Mentor Rating 4.2']);
        $mentorProfile3 = Mentor::factory()->available()->for($mentor3)->create([
            'calificacionPromedio' => 4.2
        ]);
        $mentorProfile3->areasInteres()->attach([$php->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];

        $this->assertCount(3, $suggestions);
        $this->assertEquals('Mentor Rating 4.9', $suggestions[0]['name']);
        $this->assertEquals(4.9, $suggestions[0]['mentor']['calificacionPromedio']);
        $this->assertEquals('Mentor Rating 4.2', $suggestions[1]['name']);
        $this->assertEquals('Mentor Rating 3.5', $suggestions[2]['name']);
    }

    #[Test]
    public function student_without_areas_receives_empty_suggestions()
    {
        // Estudiante sin áreas de interés pero con certificado verificado
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);

        // Crear un mentor disponible
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->available()->for($mentor)->create();
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $mentorProfile->areasInteres()->attach([$php->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];
        
        $this->assertEmpty($suggestions);
    }

    #[Test]
    public function student_without_aprendiz_profile_receives_empty_suggestions()
    {
        // Estudiante sin perfil de aprendiz
        $student = User::factory()->student()->create();

        // Crear un mentor disponible
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->available()->for($mentor)->create();
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $mentorProfile->areasInteres()->attach([$php->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];
        
        // Sin perfil aprendiz, debe retornar estructura de verificación requerida
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('requires_verification', $suggestions);
        $this->assertTrue($suggestions['requires_verification']);
        $this->assertArrayHasKey('mentors', $suggestions);
        $this->assertEmpty($suggestions['mentors']);
    }

    #[Test]
    public function mentor_suggestions_limit_to_six_results()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);
        $aprendiz->areasInteres()->attach([$php->id]);

        // Crear 10 mentores
        for ($i = 1; $i <= 10; $i++) {
            $mentor = User::factory()->mentor()->create(['name' => "Mentor $i"]);
            $mentorProfile = Mentor::factory()->available()->for($mentor)->create([
                'calificacionPromedio' => 5.0 - ($i * 0.1) // Calificaciones descendentes
            ]);
            $mentorProfile->areasInteres()->attach([$php->id]);
        }

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];

        // Verificar que solo retorna máximo 6
        $this->assertCount(6, $suggestions);
        
        // Verificar que son los 6 con mejor calificación
        $this->assertEquals('Mentor 1', $suggestions[0]['name']);
        $this->assertEquals('Mentor 6', $suggestions[5]['name']);
    }

    #[Test]
    public function mentor_suggestions_include_all_required_fields()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);
        $aprendiz->areasInteres()->attach([$php->id]);

        $mentor = User::factory()->mentor()->create(['name' => 'Test Mentor']);
        $mentorProfile = Mentor::factory()->available()->for($mentor)->create([
            'experiencia' => 'Experiencia detallada del mentor en desarrollo web y aplicaciones empresariales',
            'biografia' => 'Biografía completa del mentor con más de 100 caracteres para cumplir con las validaciones del sistema de perfiles',
            'años_experiencia' => 10,
            'disponibilidad' => 'Lunes a Viernes',
            'disponibilidad_detalle' => '9am - 5pm',
            'disponible_ahora' => true,
            'calificacionPromedio' => 4.8
        ]);
        $mentorProfile->areasInteres()->attach([$php->id, $laravel->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];

        $this->assertCount(1, $suggestions);
        
        $suggestion = $suggestions[0];
        
        // Verificar campos principales
        $this->assertEquals('Test Mentor', $suggestion['name']);
        $this->assertArrayHasKey('id', $suggestion);
        $this->assertArrayHasKey('mentor', $suggestion);
        
        // Verificar campos del mentor
        $mentorData = $suggestion['mentor'];
        $this->assertStringContainsString('Experiencia detallada', $mentorData['experiencia']);
        $this->assertStringContainsString('Biografía completa', $mentorData['biografia']);
        $this->assertEquals(10, $mentorData['años_experiencia']);
        $this->assertEquals('Lunes a Viernes', $mentorData['disponibilidad']);
        $this->assertEquals('9am - 5pm', $mentorData['disponibilidad_detalle']);
        $this->assertTrue($mentorData['disponible_ahora']);
        $this->assertEquals(4.8, $mentorData['calificacionPromedio']);
        
        // Verificar propiedades calculadas
        $this->assertArrayHasKey('stars_rating', $mentorData);
        $this->assertArrayHasKey('rating_percentage', $mentorData);
        
        // Verificar áreas de interés
        $this->assertArrayHasKey('areas_interes', $mentorData);
        $this->assertCount(2, $mentorData['areas_interes']);
    }

    #[Test]
    public function mentor_suggestions_use_cache_for_performance()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => true // Requerido desde US2.5
        ]);
        $aprendiz->areasInteres()->attach([$php->id]);

        $mentor = User::factory()->mentor()->create(['name' => 'Cached Mentor']);
        $mentorProfile = Mentor::factory()->available()->for($mentor)->create();
        $mentorProfile->areasInteres()->attach([$php->id]);

        // Primera petición - debe cachear
        $this->actingAs($student)->get(route('student.dashboard'));

        // Verificar que el cache fue creado
        $studentAreaIds = $aprendiz->areasInteres->pluck('id');
        $cacheKey = 'mentor_suggestions_' . md5($studentAreaIds->sort()->implode(','));
        
        $this->assertTrue(Cache::has($cacheKey));

        // Eliminar el mentor de la BD
        $mentor->delete();

        // Segunda petición - debe retornar datos cacheados (mentor aún aparece)
        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];

        // El mentor debe aparecer porque está cacheado
        $this->assertCount(1, $suggestions);
        $this->assertEquals('Cached Mentor', $suggestions[0]['name']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_student_dashboard()
    {
        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function student_without_verified_certificate_receives_verification_requirement()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);

        // Estudiante SIN certificado verificado
        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create([
            'certificate_verified' => false // No verificado
        ]);
        $aprendiz->areasInteres()->attach([$php->id]);

        // Crear mentor disponible
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->available()->for($mentor)->create();
        $mentorProfile->areasInteres()->attach([$php->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $suggestions = $response->viewData('page')['props']['mentorSuggestions'];

        // Debe retornar estructura de verificación requerida
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('requires_verification', $suggestions);
        $this->assertTrue($suggestions['requires_verification']);
        $this->assertArrayHasKey('message', $suggestions);
        $this->assertStringContainsString('certificado', $suggestions['message']);
        $this->assertArrayHasKey('mentors', $suggestions);
        $this->assertEmpty($suggestions['mentors']); // No debe mostrar mentores
    }
}
