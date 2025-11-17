<?php

namespace Tests\Feature;

use App\Models\Aprendiz;
use App\Models\AreaInteres;
use App\Models\Mentor;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCertificateVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test approved certificate sets certificate_verified to true.
     */
    public function test_approved_certificate_sets_certificate_verified_to_true(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Simular procesamiento que aprueba el certificado
        $document->update([
            'status' => 'approved',
            'keyword_score' => 85,
            'processed_at' => now(),
        ]);

        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test mentor suggestions are blocked without verified certificate.
     */
    public function test_mentor_suggestions_are_blocked_without_verified_certificate(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $student->id,
            'certificate_verified' => false,
        ]);

        // Crear algunas áreas de interés para el estudiante
        $area = AreaInteres::factory()->create();
        $aprendiz->areasInteres()->attach($area->id);

        // Crear un mentor disponible
        $mentorUser = User::factory()->create(['role' => 'mentor']);
        $mentor = Mentor::factory()->create([
            'user_id' => $mentorUser->id,
            'disponible_ahora' => true,
        ]);
        $mentor->areasInteres()->attach($area->id);

        $this->actingAs($student);

        $response = $this->get(route('student.dashboard'));

        $response->assertStatus(200);
        
        // Verificar que Inertia recibe la estructura de bloqueo
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Dashboard/Index')
                 ->has('mentorSuggestions')
                 ->where('mentorSuggestions.requires_verification', true)
                 ->where('mentorSuggestions.mentors', [])
        );
    }

    /**
     * Test mentor suggestions are allowed with verified certificate.
     */
    public function test_mentor_suggestions_are_allowed_with_verified_certificate(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $student->id,
            'certificate_verified' => true,
        ]);

        // Crear área de interés
        $area = AreaInteres::factory()->create();
        $aprendiz->areasInteres()->attach($area->id);

        // Crear mentor disponible con la misma área
        $mentorUser = User::factory()->create(['role' => 'mentor']);
        $mentor = Mentor::factory()->create([
            'user_id' => $mentorUser->id,
            'disponible_ahora' => true,
            'cv_verified' => true,
        ]);
        $mentor->areasInteres()->attach($area->id);

        $this->actingAs($student);

        $response = $this->get(route('student.dashboard'));

        $response->assertStatus(200);
        
        // Verificar que Inertia recibe sugerencias de mentores (sin bloqueo)
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Dashboard/Index')
                 ->has('mentorSuggestions')
                 ->missing('mentorSuggestions.requires_verification')
        );
    }

    /**
     * Test rejected certificate allows resubmission.
     */
    public function test_rejected_certificate_allows_resubmission(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        // Crear un certificado rechazado
        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejected',
            'keyword_score' => 20,
            'rejection_reason' => 'No contiene información suficiente',
            'processed_at' => now(),
        ]);

        $this->actingAs($user);

        // El estudiante debería poder ver su perfil y resubir
        $response = $this->get(route('profile.edit'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Profile/Edit')
                 ->has('certificate')
                 ->where('certificate.status', 'rejected')
        );

        // Verificar que certificate_verified sigue siendo false
        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test student without aprendiz profile is blocked from suggestions.
     */
    public function test_student_without_aprendiz_profile_is_blocked_from_suggestions(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        // No crear aprendiz

        $this->actingAs($student);

        $response = $this->get(route('student.dashboard'));

        $response->assertStatus(200);
        
        // Debe retornar estructura de bloqueo
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Dashboard/Index')
                 ->has('mentorSuggestions')
                 ->where('mentorSuggestions.requires_verification', true)
        );
    }

    /**
     * Test multiple documents - only needs one approved.
     */
    public function test_student_with_one_approved_certificate_gets_verified(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        // Crear un certificado rechazado
        StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejected',
        ]);

        // Crear un certificado pending y luego aprobarlo (para activar Observer)
        $approvedDoc = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        
        $approvedDoc->update([
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test verification flag persists across sessions.
     */
    public function test_verification_flag_persists_across_sessions(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        // Crear certificado pending y aprobarlo (para activar Observer)
        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        
        $document->update([
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        // Simular nueva sesión
        $this->actingAs($user);
        
        $response = $this->get(route('student.dashboard'));
        
        // Debe permitir ver sugerencias si tiene áreas configuradas
        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test student can see certificate status in profile.
     */
    public function test_student_can_see_certificate_status_in_profile(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Aprendiz::factory()->create(['user_id' => $user->id]);

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'keyword_score' => 75,
            'processed_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Profile/Edit')
                 ->has('certificate')
                 ->where('certificate.status', 'approved')
                 ->where('certificate.keyword_score', 75)
        );
    }

    /**
     * Test pending certificate does not grant verification.
     */
    public function test_pending_certificate_does_not_grant_verification(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test invalid certificate does not grant verification.
     */
    public function test_invalid_certificate_does_not_grant_verification(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => false,
        ]);

        StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'invalid',
            'rejection_reason' => 'Error al procesar',
            'processed_at' => now(),
        ]);

        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test verification message includes action link.
     */
    public function test_verification_message_includes_upload_url(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $student->id,
            'certificate_verified' => false,
        ]);

        $area = AreaInteres::factory()->create();
        $aprendiz->areasInteres()->attach($area->id);

        $this->actingAs($student);

        $response = $this->get(route('student.dashboard'));

        $response->assertInertia(fn ($page) => 
            $page->component('Student/Dashboard/Index')
                 ->has('mentorSuggestions.upload_url')
                 ->where('mentorSuggestions.action', 'upload_certificate')
        );
    }
}
