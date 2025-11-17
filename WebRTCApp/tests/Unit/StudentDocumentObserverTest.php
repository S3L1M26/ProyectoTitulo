<?php

namespace Tests\Unit;

use App\Models\Aprendiz;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDocumentObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test observer sets certificate_verified to true when document is approved.
     */
    public function test_approved_document_sets_certificate_verified_to_true(): void
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

        // Cambiar estado a approved
        $document->update(['status' => 'approved']);

        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test observer sets certificate_verified to false when document is rejected.
     */
    public function test_rejected_document_sets_certificate_verified_to_false(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Cambiar estado a rejected
        $document->update(['status' => 'rejected']);

        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test observer sets certificate_verified to false when document is invalid.
     */
    public function test_invalid_document_sets_certificate_verified_to_false(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Cambiar estado a invalid
        $document->update(['status' => 'invalid']);

        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test observer does not remove verification if another approved certificate exists.
     */
    public function test_does_not_remove_verification_if_another_approved_certificate_exists(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        // Crear dos certificados aprobados
        $document1 = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $document2 = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Rechazar uno de ellos
        $document1->update(['status' => 'rejected']);

        $aprendiz->refresh();
        // Debe mantener la verificación porque document2 sigue aprobado
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test observer removes verification when approved document is deleted.
     */
    public function test_removes_verification_when_approved_document_is_deleted(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Eliminar el documento
        $document->delete();

        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test observer does not remove verification on delete if another approved exists.
     */
    public function test_does_not_remove_verification_on_delete_if_another_approved_exists(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        // Crear dos certificados aprobados
        $document1 = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $document2 = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Eliminar uno
        $document1->delete();

        $aprendiz->refresh();
        // Debe mantener la verificación porque document2 sigue aprobado
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test observer does nothing when pending document is updated to pending.
     */
    public function test_observer_does_nothing_when_status_does_not_change_significantly(): void
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

        // Actualizar otros campos sin cambiar status
        $document->update(['keyword_score' => 50]);

        $aprendiz->refresh();
        // No debe cambiar
        $this->assertFalse($aprendiz->certificate_verified);
    }

    /**
     * Test observer handles user without aprendiz profile gracefully.
     */
    public function test_observer_handles_user_without_aprendiz_profile_gracefully(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        // No crear aprendiz

        $document = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // No debe lanzar error
        $document->update(['status' => 'approved']);

        // Verificar que no causó error
        $this->assertEquals('approved', $document->fresh()->status);
    }

    /**
     * Test multiple status changes are handled correctly.
     */
    public function test_multiple_status_changes_are_handled_correctly(): void
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

        // Aprobar
        $document->update(['status' => 'approved']);
        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);

        // Rechazar
        $document->update(['status' => 'rejected']);
        $aprendiz->refresh();
        $this->assertFalse($aprendiz->certificate_verified);

        // Aprobar de nuevo
        $document->update(['status' => 'approved']);
        $aprendiz->refresh();
        $this->assertTrue($aprendiz->certificate_verified);
    }

    /**
     * Test deleting non-approved document does not affect verification.
     */
    public function test_deleting_non_approved_document_does_not_affect_verification(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $aprendiz = Aprendiz::factory()->create([
            'user_id' => $user->id,
            'certificate_verified' => true,
        ]);

        // Crear un documento aprobado
        StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        // Crear y eliminar un documento rechazado
        $rejectedDoc = StudentDocument::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejected',
        ]);

        $rejectedDoc->delete();

        $aprendiz->refresh();
        // Debe mantener la verificación
        $this->assertTrue($aprendiz->certificate_verified);
    }
}
