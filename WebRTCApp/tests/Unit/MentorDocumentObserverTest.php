<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MentorDocumentObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_document_sets_cv_verified_to_true(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear documento en estado pending primero
        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();

        // Actualizar a approved para disparar el Observer
        $document->update(['status' => 'approved']);

        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_rejected_document_sets_cv_verified_to_false(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => true]);

        $document = MentorDocument::factory()->approved()->for($user, 'user')->create();
        
        // Cambiar a rejected
        $document->update(['status' => 'rejected']);

        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_invalid_document_sets_cv_verified_to_false(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => true]);

        $document = MentorDocument::factory()->approved()->for($user, 'user')->create();
        
        // Cambiar a invalid
        $document->update(['status' => 'invalid']);

        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_does_not_remove_verification_if_another_approved_cv_exists(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => true]);

        // Crear dos CVs aprobados
        $document1 = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document1->update(['status' => 'approved']);
        
        $document2 = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document2->update(['status' => 'approved']);

        // Rechazar el primero
        $document1->update(['status' => 'rejected']);

        // El mentor debe mantener la verificación porque tiene document2 aprobado
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_removes_verification_when_approved_document_is_deleted(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document->update(['status' => 'approved']);

        $this->assertTrue($mentor->fresh()->cv_verified);

        // Eliminar el documento aprobado
        $document->delete();

        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_does_not_remove_verification_on_delete_if_another_approved_exists(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear dos CVs aprobados
        $document1 = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document1->update(['status' => 'approved']);
        
        $document2 = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $document2->update(['status' => 'approved']);

        $this->assertTrue($mentor->fresh()->cv_verified);

        // Eliminar el primero
        $document1->delete();

        // Debe mantener verificación por document2
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_observer_does_nothing_when_status_does_not_change_significantly(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();

        // Actualizar otro campo sin cambiar status
        $document->update(['keyword_score' => 75]);

        // No debe afectar cv_verified
        $this->assertFalse($mentor->fresh()->cv_verified);
    }

    public function test_observer_handles_user_without_mentor_profile_gracefully(): void
    {
        // Usuario sin perfil de mentor
        $user = User::factory()->mentor()->create();
        // No crear Mentor asociado

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();

        // No debe lanzar error al actualizar
        $document->update(['status' => 'approved']);

        // Verificar que el documento se actualizó correctamente
        $this->assertEquals('approved', $document->fresh()->status);
    }

    public function test_multiple_status_changes_are_handled_correctly(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();

        // pending → approved
        $document->update(['status' => 'approved']);
        $this->assertTrue($mentor->fresh()->cv_verified);

        // approved → rejected
        $document->update(['status' => 'rejected']);
        $this->assertFalse($mentor->fresh()->cv_verified);

        // rejected → approved (reprocessing)
        $document->update(['status' => 'approved']);
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_deleting_non_approved_document_does_not_affect_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear documento aprobado
        $approvedDoc = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $approvedDoc->update(['status' => 'approved']);
        $this->assertTrue($mentor->fresh()->cv_verified);

        // Crear documento rechazado
        $rejectedDoc = MentorDocument::factory()->rejected()->for($user, 'user')->create();

        // Eliminar el documento rechazado
        $rejectedDoc->delete();

        // No debe afectar la verificación
        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_pending_to_approved_transition_grants_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        $document = MentorDocument::factory()->pending()->for($user, 'user')->create();
        $this->assertFalse($mentor->fresh()->cv_verified);

        $document->update([
            'status' => 'approved',
            'keyword_score' => 85,
            'processed_at' => now(),
        ]);

        $this->assertTrue($mentor->fresh()->cv_verified);
    }

    public function test_creating_approved_document_does_not_trigger_verification(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create(['cv_verified' => false]);

        // Crear directamente con status='approved' no dispara el Observer updated
        MentorDocument::factory()->approved()->for($user, 'user')->create();

        // Debe permanecer false porque el Observer solo se dispara en 'updated'
        $this->assertFalse($mentor->fresh()->cv_verified);
    }
}
