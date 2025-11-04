<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MentorDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_mentor_document_belongs_to_user(): void
    {
        $user = User::factory()->mentor()->create();
        $document = MentorDocument::factory()->for($user, 'user')->create();

        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }

    public function test_mentor_document_belongs_to_mentor(): void
    {
        $user = User::factory()->mentor()->create();
        $mentor = Mentor::factory()->for($user)->create();
        $document = MentorDocument::factory()->for($user, 'user')->create();

        $this->assertInstanceOf(Mentor::class, $document->mentor);
        $this->assertEquals($mentor->id, $document->mentor->id);
    }

    public function test_approved_scope_returns_only_approved_documents(): void
    {
        MentorDocument::factory()->approved()->create();
        MentorDocument::factory()->pending()->create();
        MentorDocument::factory()->rejected()->create();

        $approved = MentorDocument::approved()->get();

        $this->assertCount(1, $approved);
        $this->assertEquals('approved', $approved->first()->status);
    }

    public function test_pending_scope_returns_only_pending_documents(): void
    {
        MentorDocument::factory()->approved()->create();
        MentorDocument::factory()->pending()->create();
        MentorDocument::factory()->rejected()->create();

        $pending = MentorDocument::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    public function test_rejected_scope_returns_only_rejected_documents(): void
    {
        MentorDocument::factory()->approved()->create();
        MentorDocument::factory()->pending()->create();
        MentorDocument::factory()->rejected()->create();

        $rejected = MentorDocument::rejected()->get();

        $this->assertCount(1, $rejected);
        $this->assertEquals('rejected', $rejected->first()->status);
    }

    public function test_public_scope_returns_only_public_documents(): void
    {
        MentorDocument::factory()->public()->create();
        MentorDocument::factory()->private()->create();

        $public = MentorDocument::public()->get();

        $this->assertCount(1, $public);
        $this->assertTrue($public->first()->is_public);
    }

    public function test_is_approved_returns_true_for_approved_status(): void
    {
        $document = MentorDocument::factory()->approved()->create();

        $this->assertTrue($document->isApproved());
    }

    public function test_is_pending_returns_true_for_pending_status(): void
    {
        $document = MentorDocument::factory()->pending()->create();

        $this->assertTrue($document->isPending());
    }

    public function test_is_rejected_returns_true_for_rejected_status(): void
    {
        $document = MentorDocument::factory()->rejected()->create();

        $this->assertTrue($document->isRejected());
    }

    public function test_is_invalid_returns_true_for_invalid_status(): void
    {
        $document = MentorDocument::factory()->invalid()->create();

        $this->assertTrue($document->isInvalid());
    }

    public function test_is_public_returns_true_for_public_document(): void
    {
        $document = MentorDocument::factory()->public()->create();

        $this->assertTrue($document->isPublic());
    }

    public function test_is_public_returns_false_for_private_document(): void
    {
        $document = MentorDocument::factory()->private()->create();

        $this->assertFalse($document->isPublic());
    }

    public function test_processed_at_is_cast_to_datetime(): void
    {
        $document = MentorDocument::factory()->create([
            'processed_at' => '2024-01-15 10:30:00'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $document->processed_at);
    }

    public function test_keyword_score_is_cast_to_integer(): void
    {
        $document = MentorDocument::factory()->create([
            'keyword_score' => '85'
        ]);

        $this->assertIsInt($document->keyword_score);
        $this->assertEquals(85, $document->keyword_score);
    }

    public function test_is_public_is_cast_to_boolean(): void
    {
        $document = MentorDocument::factory()->create([
            'is_public' => 1
        ]);

        $this->assertIsBool($document->is_public);
        $this->assertTrue($document->is_public);
    }

    public function test_soft_deletes_work_correctly(): void
    {
        $document = MentorDocument::factory()->create();
        
        $document->delete();

        $this->assertSoftDeleted($document);
        $this->assertNotNull($document->deleted_at);
    }

    public function test_fillable_attributes_are_mass_assignable(): void
    {
        $data = [
            'user_id' => User::factory()->mentor()->create()->id,
            'file_path' => 'mentor_cvs/test.pdf',
            'extracted_text' => 'Test CV text',
            'keyword_score' => 75,
            'status' => 'approved',
            'rejection_reason' => null,
            'is_public' => true,
        ];

        $document = MentorDocument::create($data);

        $this->assertEquals($data['file_path'], $document->file_path);
        $this->assertEquals($data['keyword_score'], $document->keyword_score);
        $this->assertEquals($data['status'], $document->status);
        $this->assertTrue($document->is_public);
    }
}
