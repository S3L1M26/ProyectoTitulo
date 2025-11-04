<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class MentorCVVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_cv_sets_cv_verified_to_true(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear CV pending y luego aprobar (dispara Observer)
        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();
        
        $this->assertFalse($mentor->fresh()->cv_verified);

        // Simular aprobación por el Observer/Job
        $document->update([
            'status' => 'approved',
            'keyword_score' => 75,
            'processed_at' => now(),
        ]);

        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_toggle_disponibilidad_is_blocked_without_verified_cv(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => false,
            'disponible_ahora' => false,
            'experiencia' => str_repeat('Experiencia en desarrollo web con Laravel y PHP. ', 10),
            'biografia' => str_repeat('Soy un desarrollador fullstack apasionado por la tecnología. ', 5),
            'años_experiencia' => 5,
        ]);
        $mentor->areasInteres()->attach(\App\Models\AreaInteres::factory()->create()->id);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors(['cv_verification']);
        $response->assertSessionHas('cv_upload_required');
        
        // Disponibilidad no debe cambiar
        $this->assertFalse($mentor->fresh()->disponible_ahora);
    }

    public function test_toggle_disponibilidad_is_allowed_with_verified_cv(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => true,
            'disponible_ahora' => false,
            'experiencia' => str_repeat('Experiencia en desarrollo web con Laravel y PHP. ', 10),
            'biografia' => str_repeat('Soy un desarrollador fullstack apasionado por la tecnología. ', 5),
            'años_experiencia' => 5,
        ]);
        $mentor->areasInteres()->attach(\App\Models\AreaInteres::factory()->create()->id);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasNoErrors();
        
        // Disponibilidad debe cambiar
        $this->assertTrue($mentor->fresh()->disponible_ahora);
    }

    public function test_rejected_cv_allows_resubmission(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear CV rechazado
        $rejectedDoc = MentorDocument::factory()->rejected()->for($user, 'user')->create();

        $this->assertEquals('rejected', $rejectedDoc->status);
        $this->assertFalse($mentor->fresh()->cv_verified);

        // El mentor puede subir nuevo CV (no hay restricción en el código)
        $newDoc = MentorDocument::factory()->pending()->for($user, 'user')->create();
        
        $this->assertEquals('pending', $newDoc->status);
        
        // Aprobar el nuevo
        $newDoc->update(['status' => 'approved', 'keyword_score' => 80, 'processed_at' => now()]);
        
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_mentor_without_cv_verified_cannot_activate_disponibilidad(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => false,
            'disponible_ahora' => false,
            'experiencia' => str_repeat('Desarrollo fullstack con muchos años de experiencia. ', 10),
            'biografia' => str_repeat('Biografía completa del mentor con información relevante. ', 5),
            'años_experiencia' => 8,
        ]);
        $mentor->areasInteres()->attach(\App\Models\AreaInteres::factory()->create()->id);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true,
        ]);

        $response->assertSessionHasErrors(['cv_verification']);
        $this->assertFalse($mentor->fresh()->disponible_ahora);
    }

    public function test_mentor_with_verified_cv_can_deactivate_disponibilidad(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => true,
            'disponible_ahora' => true,
        ]);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => false,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasNoErrors();
        
        // Debe poder desactivar sin CV (solo activar requiere CV)
        $this->assertFalse($mentor->fresh()->disponible_ahora);
    }

    public function test_cv_verification_persists_across_sessions(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document->update(['status' => 'approved', 'keyword_score' => 85, 'processed_at' => now()]);

        $this->assertTrue($mentor->fresh()->cv_verified);

        // Simular nueva sesión
        $this->actingAs($user);
        
        // Verificación debe persistir
        $this->assertTrue($user->mentor->fresh()->cv_verified);
    }

    public function test_mentor_can_see_cv_status_in_profile(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);
        
        $document = MentorDocument::factory()->approved()->for($user, 'user')->create([
            'keyword_score' => 85,
            'is_public' => true,
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $assert) => $assert
            ->component('Profile/Edit')
            ->has('mentorCv')
            ->where('mentorCv.status', 'approved')
            ->where('mentorCv.keyword_score', 85)
            ->where('mentorCv.is_public', true)
        );
    }

    public function test_pending_cv_does_not_grant_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        MentorDocument::factory()->pending()->for($user, 'user')->create();

        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_invalid_cv_does_not_grant_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        MentorDocument::factory()->invalid()->for($user, 'user')->create();

        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_verification_message_includes_upload_url(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => false,
            'disponible_ahora' => false,
            'experiencia' => str_repeat('Mucha experiencia en desarrollo. ', 10),
            'biografia' => str_repeat('Biografía completa y detallada del mentor profesional. ', 5),
            'años_experiencia' => 7,
        ]);
        $mentor->areasInteres()->attach(\App\Models\AreaInteres::factory()->create()->id);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true,
        ]);

        $response->assertSessionHas('cv_upload_required', function ($value) {
            return isset($value['upload_url']) && 
                   str_contains($value['upload_url'], route('mentor.cv.upload'));
        });
    }

    public function test_multiple_cvs_one_approved_grants_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear varios CVs: rechazado, pending, aprobado
        MentorDocument::factory()->rejected()->for($user, 'user')->create();
        MentorDocument::factory()->pending()->for($user, 'user')->create();
        
        $approvedDoc = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $approvedDoc->update(['status' => 'approved', 'keyword_score' => 90, 'processed_at' => now()]);

        // Con un CV aprobado, debe tener verificación
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_mentor_without_profile_data_cannot_activate_disponibilidad_even_with_cv(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create([
            'cv_verified' => true,
            'disponible_ahora' => false,
            'experiencia' => null, // Falta datos
            'biografia' => null,
            'años_experiencia' => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.mentor.toggle-disponibilidad'), [
            'disponible' => true,
        ]);

        // Debe fallar por falta de información del perfil (no por CV)
        $response->assertSessionHasErrors();
        $this->assertFalse($mentor->fresh()->disponible_ahora);
    }
}

