<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\AreaInteres;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class UserCompletenessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function student_with_complete_profile_has_100_percent_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $aprendiz = Aprendiz::factory()->for($user)->create([
            'semestre' => 5,
            'objetivos' => 'Aprender programación avanzada y desarrollo web full-stack'
        ]);
        
        $areas = AreaInteres::factory()->count(2)->create();
        $aprendiz->areasInteres()->attach($areas);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(100, $completeness['percentage']);
        $this->assertEmpty($completeness['missing_fields']);
        $this->assertCount(3, $completeness['completed_fields']);
        $this->assertContains('semestre', $completeness['completed_fields']);
        $this->assertContains('areas_interes', $completeness['completed_fields']);
        $this->assertContains('objetivos', $completeness['completed_fields']);
    }

    #[Test]
    public function student_without_areas_has_partial_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        Aprendiz::factory()->for($user)->create([
            'semestre' => 3,
            'objetivos' => 'Mejorar mis habilidades de programación'
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        // semestre (35%) + objetivos (25%) = 60%
        $this->assertEquals(60, $completeness['percentage']);
        $this->assertContains('Áreas de interés', $completeness['missing_fields']);
        $this->assertCount(1, $completeness['missing_fields']);
        $this->assertCount(2, $completeness['completed_fields']);
    }

    #[Test]
    public function student_without_semestre_has_low_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        Aprendiz::factory()->for($user)->create([
            'semestre' => null,
            'objetivos' => 'Aprender desarrollo web'
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        // Solo objetivos (25%)
        $this->assertEquals(25, $completeness['percentage']);
        $this->assertContains('Semestre', $completeness['missing_fields']);
        $this->assertContains('Áreas de interés', $completeness['missing_fields']);
        $this->assertCount(2, $completeness['missing_fields']);
    }

    #[Test]
    public function student_with_empty_profile_has_zero_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        Aprendiz::factory()->for($user)->create([
            'semestre' => null,
            'objetivos' => null
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(0, $completeness['percentage']);
        $this->assertCount(3, $completeness['missing_fields']);
        $this->assertEmpty($completeness['completed_fields']);
    }

    #[Test]
    public function student_without_aprendiz_record_has_zero_completeness()
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $completeness = $user->profile_completeness;
        
        $this->assertEquals(0, $completeness['percentage']);
        $this->assertCount(3, $completeness['missing_fields']);
    }

    #[Test]
    public function mentor_with_complete_profile_has_100_percent_completeness()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        $mentor = Mentor::factory()->for($user)->create([
            'experiencia' => str_repeat('Experiencia detallada en desarrollo de software. ', 5), // >50 chars
            'biografia' => str_repeat('Soy un desarrollador con amplia experiencia en múltiples tecnologías y frameworks modernos. ', 3), // >100 chars
            'años_experiencia' => 8,
            'disponibilidad' => 'Lunes a Viernes 14:00-18:00'
        ]);
        
        $areas = AreaInteres::factory()->count(3)->create();
        $mentor->areasInteres()->attach($areas);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertEquals(100, $completeness['percentage']);
        $this->assertEmpty($completeness['missing_fields']);
        $this->assertCount(5, $completeness['completed_fields']);
    }

    #[Test]
    public function mentor_with_short_experiencia_fails_validation()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($user)->create([
            'experiencia' => 'Corto', // <50 chars
            'biografia' => str_repeat('Biografía completa. ', 10),
            'años_experiencia' => 5,
            'disponibilidad' => 'Flexible'
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertLessThan(100, $completeness['percentage']);
        $this->assertContains('Experiencia profesional detallada', $completeness['missing_fields']);
    }

    #[Test]
    public function mentor_with_short_biografia_fails_validation()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($user)->create([
            'experiencia' => str_repeat('Experiencia suficiente. ', 5),
            'biografia' => 'Bio breve', // <100 chars
            'años_experiencia' => 3,
            'disponibilidad' => 'Tardes'
        ]);
        
        $completeness = $user->fresh()->profile_completeness;
        
        $this->assertLessThan(100, $completeness['percentage']);
        $this->assertContains('Biografía personal', $completeness['missing_fields']);
    }

    #[Test]
    public function mentor_without_areas_has_partial_completeness()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        
        Mentor::factory()->for($user)->create([
            'experiencia' => str_repeat('Experiencia detallada. ', 5),
            'biografia' => str_repeat('Biografía completa. ', 12),
            'años_experiencia' => 10,
            'disponibilidad' => 'Lunes a Viernes'
        ]);
        // No adjuntar áreas de interés
        
        $completeness = $user->fresh()->profile_completeness;
        
        // 30% + 20% + 15% + 10% = 75% (sin áreas que valen 25%)
        $this->assertEquals(75, $completeness['percentage']);
        $this->assertContains('Áreas de especialidad', $completeness['missing_fields']);
    }

    #[Test]
    public function profile_completeness_weights_sum_to_100_for_student()
    {
        $user = User::factory()->create(['role' => 'student']);
        Aprendiz::factory()->for($user)->create();
        
        $completeness = $user->profile_completeness;
        $weights = $completeness['weights'];
        
        $totalWeight = array_sum($weights);
        
        $this->assertEquals(100, $totalWeight);
    }

    #[Test]
    public function profile_completeness_weights_sum_to_100_for_mentor()
    {
        $user = User::factory()->create(['role' => 'mentor']);
        Mentor::factory()->for($user)->create();
        
        $completeness = $user->profile_completeness;
        $weights = $completeness['weights'];
        
        $totalWeight = array_sum($weights);
        
        $this->assertEquals(100, $totalWeight);
    }

    #[Test]
    public function user_without_role_returns_default_completeness()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $completeness = $user->profile_completeness;
        
        $this->assertEquals(100, $completeness['percentage']);
        $this->assertEmpty($completeness['missing_fields']);
    }
}
