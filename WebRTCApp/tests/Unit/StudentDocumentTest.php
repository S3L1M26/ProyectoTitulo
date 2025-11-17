<?php

namespace Tests\Unit;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test student document belongs to user.
     */
    public function test_student_document_belongs_to_user(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $document = StudentDocument::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }

    /**
     * Test approved scope filters only approved documents.
     */
    public function test_approved_scope_returns_only_approved_documents(): void
    {
        StudentDocument::factory()->create(['status' => 'approved']);
        StudentDocument::factory()->create(['status' => 'pending']);
        StudentDocument::factory()->create(['status' => 'rejected']);

        $approved = StudentDocument::approved()->get();

        $this->assertCount(1, $approved);
        $this->assertEquals('approved', $approved->first()->status);
    }

    /**
     * Test pending scope filters only pending documents.
     */
    public function test_pending_scope_returns_only_pending_documents(): void
    {
        StudentDocument::factory()->create(['status' => 'approved']);
        StudentDocument::factory()->create(['status' => 'pending']);
        StudentDocument::factory()->create(['status' => 'pending']);

        $pending = StudentDocument::pending()->get();

        $this->assertCount(2, $pending);
        $this->assertTrue($pending->every(fn($doc) => $doc->status === 'pending'));
    }

    /**
     * Test rejected scope filters only rejected documents.
     */
    public function test_rejected_scope_returns_only_rejected_documents(): void
    {
        StudentDocument::factory()->create(['status' => 'approved']);
        StudentDocument::factory()->create(['status' => 'rejected']);
        StudentDocument::factory()->create(['status' => 'rejected']);

        $rejected = StudentDocument::rejected()->get();

        $this->assertCount(2, $rejected);
        $this->assertTrue($rejected->every(fn($doc) => $doc->status === 'rejected'));
    }

    /**
     * Test isApproved helper method.
     */
    public function test_is_approved_returns_true_for_approved_status(): void
    {
        $approved = StudentDocument::factory()->create(['status' => 'approved']);
        $pending = StudentDocument::factory()->create(['status' => 'pending']);

        $this->assertTrue($approved->isApproved());
        $this->assertFalse($pending->isApproved());
    }

    /**
     * Test isPending helper method.
     */
    public function test_is_pending_returns_true_for_pending_status(): void
    {
        $pending = StudentDocument::factory()->create(['status' => 'pending']);
        $approved = StudentDocument::factory()->create(['status' => 'approved']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($approved->isPending());
    }

    /**
     * Test isRejected helper method.
     */
    public function test_is_rejected_returns_true_for_rejected_status(): void
    {
        $rejected = StudentDocument::factory()->create(['status' => 'rejected']);
        $approved = StudentDocument::factory()->create(['status' => 'approved']);

        $this->assertTrue($rejected->isRejected());
        $this->assertFalse($approved->isRejected());
    }

    /**
     * Test isInvalid helper method.
     */
    public function test_is_invalid_returns_true_for_invalid_status(): void
    {
        $invalid = StudentDocument::factory()->create(['status' => 'invalid']);
        $approved = StudentDocument::factory()->create(['status' => 'approved']);

        $this->assertTrue($invalid->isInvalid());
        $this->assertFalse($approved->isInvalid());
    }

    /**
     * Test processed_at is cast to datetime.
     */
    public function test_processed_at_is_cast_to_datetime(): void
    {
        $document = StudentDocument::factory()->create([
            'processed_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $document->processed_at);
    }

    /**
     * Test keyword_score is cast to integer.
     */
    public function test_keyword_score_is_cast_to_integer(): void
    {
        $document = StudentDocument::factory()->create([
            'keyword_score' => '75',
        ]);

        $this->assertIsInt($document->keyword_score);
        $this->assertEquals(75, $document->keyword_score);
    }

    /**
     * Test soft deletes work correctly.
     */
    public function test_soft_deletes_work_correctly(): void
    {
        $document = StudentDocument::factory()->create();
        $id = $document->id;

        $document->delete();

        $this->assertSoftDeleted('student_documents', ['id' => $id]);
        $this->assertNotNull($document->fresh()->deleted_at);
    }

    /**
     * Test fillable attributes are mass assignable.
     */
    public function test_fillable_attributes_are_mass_assignable(): void
    {
        $data = [
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'file_path' => 'certificates/test.pdf',
            'extracted_text' => 'Test text',
            'keyword_score' => 85,
            'status' => 'approved',
            'processed_at' => now(),
            'rejection_reason' => null,
        ];

        $document = StudentDocument::create($data);

        $this->assertDatabaseHas('student_documents', [
            'user_id' => $data['user_id'],
            'file_path' => 'certificates/test.pdf',
            'keyword_score' => 85,
            'status' => 'approved',
        ]);
    }
}
