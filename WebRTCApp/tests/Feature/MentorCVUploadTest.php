<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorDocument;
use App\Jobs\ProcessMentorCVJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class MentorCVUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        Storage::fake('local');
    }

    public function test_authenticated_mentor_can_upload_valid_pdf_cv(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('cv.pdf', 1024); // 1MB PDF

        $response = $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
            'is_public' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verificar que se guardó en storage (el controller usa timestamp como nombre)
        $files = Storage::disk('local')->files("mentor_cvs/{$mentor->id}");
        $this->assertCount(1, $files);
        $this->assertStringContainsString('_cv.pdf', $files[0]);
        
        // Verificar que se creó el documento en BD
        $this->assertDatabaseHas('mentor_documents', [
            'user_id' => $mentor->id,
            'status' => 'pending',
            'is_public' => true,
        ]);
        
        // Verificar que se dispatched el job
        Queue::assertPushed(ProcessMentorCVJob::class);
    }

    public function test_upload_rejects_non_pdf_files(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('document.docx', 500);

        $response = $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        $response->assertSessionHasErrors(['cv']);
        Queue::assertNothingPushed();
    }

    public function test_upload_rejects_files_larger_than_10mb(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('large_cv.pdf', 10241); // 10MB + 1KB

        $response = $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        $response->assertSessionHasErrors(['cv']);
        Queue::assertNothingPushed();
    }

    public function test_upload_accepts_files_at_exactly_10mb(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('exact_10mb.pdf', 10240); // Exactamente 10MB

        $response = $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
            'is_public' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('mentor_documents', [
            'user_id' => $mentor->id,
            'status' => 'pending',
        ]);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $response = $this->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        $response->assertRedirect(route('login'));
        Queue::assertNothingPushed();
    }

    public function test_upload_requires_mentor_role(): void
    {
        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $response = $this->actingAs($student)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        // El sistema debe rechazar estudiantes (redirect con error o 403)
        $this->assertContains($response->status(), [302, 403]);
        
        // No debe crear documento
        $this->assertDatabaseMissing('mentor_documents', [
            'user_id' => $student->id,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_upload_requires_cv_file(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $response = $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'is_public' => true,
        ]);

        $response->assertSessionHasErrors(['cv']);
        Queue::assertNothingPushed();
    }

    public function test_file_is_stored_in_correct_path_structure(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('mi_cv.pdf', 500);

        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        // Verificar que se guardó en mentor_cvs/{user_id}/
        $files = Storage::disk('local')->files("mentor_cvs/{$mentor->id}");
        $this->assertCount(1, $files);
        $this->assertStringStartsWith("mentor_cvs/{$mentor->id}/", $files[0]);
    }

    public function test_job_is_dispatched_with_correct_document_instance(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        Queue::assertPushed(ProcessMentorCVJob::class, function ($job) use ($mentor) {
            return $job->document->user_id === $mentor->id &&
                   $job->document->status === 'pending';
        });
    }

    public function test_mentor_can_upload_multiple_cvs(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Upload first CV
        $file1 = UploadedFile::fake()->create('cv_v1.pdf', 500);
        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file1,
        ]);

        // Upload second CV
        $file2 = UploadedFile::fake()->create('cv_v2.pdf', 600);
        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file2,
        ]);

        // Verificar que hay 2 documentos en BD
        $this->assertCount(2, MentorDocument::where('user_id', $mentor->id)->get());
        
        // Verificar que el job se disparó 2 veces
        Queue::assertPushed(ProcessMentorCVJob::class, 2);
    }

    public function test_upload_creates_document_with_pending_status(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
        ]);

        $document = MentorDocument::where('user_id', $mentor->id)->first();
        
        $this->assertEquals('pending', $document->status);
        $this->assertNull($document->processed_at);
        $this->assertEquals(0, $document->keyword_score); // Default es 0, no null
    }

    public function test_is_public_defaults_to_true_if_not_provided(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
            // No enviar is_public
        ]);

        $document = MentorDocument::where('user_id', $mentor->id)->first();
        $this->assertTrue($document->is_public);
    }

    public function test_mentor_can_upload_private_cv(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();
        
        $file = UploadedFile::fake()->create('cv.pdf', 1024);

        $this->actingAs($mentor)->post(route('mentor.cv.upload'), [
            'cv' => $file,
            'is_public' => false,
        ]);

        $document = MentorDocument::where('user_id', $mentor->id)->first();
        $this->assertFalse($document->is_public);
    }
}
