<?php

namespace Tests\Feature;

use App\Jobs\ProcessStudentCertificateJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentCertificateUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated student can upload valid PDF certificate.
     */
    public function test_authenticated_student_can_upload_valid_pdf_certificate(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('certificate.pdf', 1024); // 1MB

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que el archivo se guardó
        Storage::disk('local')->assertExists("student_certificates/{$user->id}/" . basename(
            \App\Models\StudentDocument::where('user_id', $user->id)->first()->file_path
        ));

        // Verificar que se creó el registro en la BD
        $this->assertDatabaseHas('student_documents', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Verificar que se despachó el job
        Queue::assertPushed(ProcessStudentCertificateJob::class, function ($job) use ($user) {
            return $job->document->user_id === $user->id;
        });
    }

    /**
     * Test upload rejects non-PDF files.
     */
    public function test_upload_rejects_non_pdf_files(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        // Intentar subir un archivo no-PDF
        $file = UploadedFile::fake()->create('document.docx', 1024);

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $response->assertSessionHasErrors('certificate');

        // Verificar que NO se creó el registro
        $this->assertDatabaseMissing('student_documents', [
            'user_id' => $user->id,
        ]);

        // Verificar que NO se despachó el job
        Queue::assertNotPushed(ProcessStudentCertificateJob::class);
    }

    /**
     * Test upload rejects files larger than 5MB.
     */
    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        // Archivo de 6MB (excede el límite de 5MB)
        $file = UploadedFile::fake()->create('certificate.pdf', 6144);

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $response->assertSessionHasErrors('certificate');

        $this->assertDatabaseMissing('student_documents', [
            'user_id' => $user->id,
        ]);

        Queue::assertNotPushed(ProcessStudentCertificateJob::class);
    }

    /**
     * Test upload requires authentication.
     */
    public function test_upload_requires_authentication(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('certificate.pdf', 1024);

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $response->assertRedirect(route('login'));

        Queue::assertNotPushed(ProcessStudentCertificateJob::class);
    }

    /**
     * Test upload requires student role.
     */
    public function test_upload_requires_student_role(): void
    {
        Storage::fake('local');
        Queue::fake();

        // Usuario con rol diferente (mentor)
        $user = User::factory()->create(['role' => 'mentor']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('certificate.pdf', 1024);

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        // Debe rechazar (puede ser 403 o redirect según middleware)
        $this->assertTrue($response->status() === 403 || $response->isRedirect());

        Queue::assertNotPushed(ProcessStudentCertificateJob::class);
    }

    /**
     * Test upload requires certificate file.
     */
    public function test_upload_requires_certificate_file(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $response = $this->post(route('student.certificate.upload'), [
            // Sin archivo
        ]);

        $response->assertSessionHasErrors('certificate');

        Queue::assertNotPushed(ProcessStudentCertificateJob::class);
    }

    /**
     * Test file is stored in correct path structure.
     */
    public function test_file_is_stored_in_correct_path_structure(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('certificate.pdf', 1024);

        $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $document = \App\Models\StudentDocument::where('user_id', $user->id)->first();

        $this->assertStringStartsWith("student_certificates/{$user->id}/", $document->file_path);
        $this->assertStringEndsWith('.pdf', $document->file_path);

        Storage::disk('local')->assertExists($document->file_path);
    }

    /**
     * Test job is dispatched with correct document instance.
     */
    public function test_job_is_dispatched_with_correct_document_instance(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('certificate.pdf', 1024);

        $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        Queue::assertPushed(ProcessStudentCertificateJob::class, function ($job) use ($user) {
            return $job->document->user_id === $user->id &&
                   $job->document->status === 'pending' &&
                   $job->document->file_path !== null;
        });
    }

    /**
     * Test student can upload multiple certificates (resubmission).
     */
    public function test_student_can_upload_multiple_certificates(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        // Primera subida
        $file1 = UploadedFile::fake()->create('certificate1.pdf', 1024);
        $this->post(route('student.certificate.upload'), [
            'certificate' => $file1,
        ]);

        // Segunda subida (reenvío)
        $file2 = UploadedFile::fake()->create('certificate2.pdf', 1024);
        $this->post(route('student.certificate.upload'), [
            'certificate' => $file2,
        ]);

        // Verificar que hay 2 documentos
        $this->assertEquals(2, \App\Models\StudentDocument::where('user_id', $user->id)->count());

        Queue::assertPushed(ProcessStudentCertificateJob::class, 2);
    }

    /**
     * Test upload creates document with correct initial status.
     */
    public function test_upload_creates_document_with_pending_status(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('certificate.pdf', 1024);

        $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $document = \App\Models\StudentDocument::where('user_id', $user->id)->first();

        $this->assertEquals('pending', $document->status);
        $this->assertNull($document->processed_at);
        $this->assertNull($document->rejection_reason);
        $this->assertEquals(0, $document->keyword_score);
    }

    /**
     * Test upload accepts PDF files at exactly 5MB.
     */
    public function test_upload_accepts_files_at_exactly_5mb(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);

        // Archivo de exactamente 5MB
        $file = UploadedFile::fake()->create('certificate.pdf', 5120);

        $response = $this->post(route('student.certificate.upload'), [
            'certificate' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_documents', [
            'user_id' => $user->id,
        ]);

        Queue::assertPushed(ProcessStudentCertificateJob::class);
    }
}
