<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\MentorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class MentorCVPublicAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_student_can_download_public_approved_cv(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Crear archivo físico en storage fake
        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        // Crear documento público y aprobado
        $document = MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cv_not_public_returns_404(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        // CV aprobado pero NO público
        MentorDocument::factory()->approved()->private()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(404);
    }

    public function test_cv_not_approved_returns_404(): void
    {
        Storage::fake('local');
        
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        // CV público pero NO aprobado (pending) - debe retornar 404
        // porque el controlador requiere status='approved' AND is_public=true
        MentorDocument::factory()->pending()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(404);
    }

    public function test_rejected_cv_returns_404_even_if_public(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        // CV rechazado pero marcado como público
        MentorDocument::factory()->rejected()->for($mentor, 'user')->create([
            'file_path' => $filePath,
            'is_public' => true,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_can_access_public_cv(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        // Sin autenticación
        $response = $this->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_mentor_can_toggle_cv_visibility_to_public(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $document = MentorDocument::factory()->approved()->private()->for($mentor, 'user')->create();

        $this->assertFalse($document->is_public);

        $response = $this->actingAs($mentor)->post(route('mentor.cv.toggle-visibility'), [
            'is_public' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue($document->fresh()->is_public);
    }

    public function test_mentor_can_toggle_cv_visibility_to_private(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $document = MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create();

        $this->assertTrue($document->is_public);

        $response = $this->actingAs($mentor)->post(route('mentor.cv.toggle-visibility'), [
            'is_public' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($document->fresh()->is_public);
    }

    public function test_only_mentor_can_toggle_visibility(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Crear CV aprobado para el mentor
        $document = MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create();

        // Estudiante intenta cambiar visibilidad
        $response = $this->actingAs($student)->post(route('mentor.cv.toggle-visibility'), [
            'is_public' => false,
        ]);

        // El middleware 'role:mentor' redirige al estudiante a su dashboard
        // sin permitir que llegue al controlador
        $response->assertRedirect(route('student.dashboard'));
        
        // El CV del mentor NO debe cambiar
        $this->assertTrue($document->fresh()->is_public);
    }

    public function test_mentor_without_approved_cv_cannot_toggle_visibility(): void
    {
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Solo CV pending
        MentorDocument::factory()->pending()->for($mentor, 'user')->create();

        $response = $this->actingAs($mentor)->post(route('mentor.cv.toggle-visibility'), [
            'is_public' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['cv']);
    }

    public function test_cv_file_not_found_returns_404(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Crear documento sin archivo físico
        MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => 'mentor_cvs/' . $mentor->id . '/nonexistent.pdf',
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(404);
    }

    public function test_nonexistent_mentor_returns_404(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('mentor.cv.show', 99999));

        $response->assertStatus(404);
    }

    public function test_cv_filename_includes_mentor_name(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create(['name' => 'Juan Pérez']);
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('CV_Mentor_Juan Pérez', $contentDisposition);
    }

    public function test_latest_approved_cv_is_returned_when_multiple_exist(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        // Crear varios CVs aprobados
        $file1 = UploadedFile::fake()->create('cv1.pdf', 1024);
        $filePath1 = $file1->store('mentor_cvs/' . $mentor->id, 'local');
        $oldDoc = MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath1,
            'processed_at' => now()->subDays(10),
        ]);

        $file2 = UploadedFile::fake()->create('cv2.pdf', 1024);
        $filePath2 = $file2->store('mentor_cvs/' . $mentor->id, 'local');
        $newDoc = MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath2,
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $response->assertStatus(200);
        
        // Verificar que retorna el más reciente comparando contenido de archivos
        $expectedContent = Storage::disk('local')->get($filePath2);
        $this->assertEquals($expectedContent, $response->getContent());
    }

    public function test_cv_is_displayed_inline_not_downloaded(): void
    {
        $student = User::factory()->student()->create();
        $mentor = User::factory()->mentor()->create();
        Mentor::factory()->for($mentor)->create();

        $file = UploadedFile::fake()->create('cv.pdf', 1024);
        $filePath = $file->store('mentor_cvs/' . $mentor->id, 'local');

        MentorDocument::factory()->approved()->public()->for($mentor, 'user')->create([
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($student)->get(route('mentor.cv.show', $mentor->id));

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('inline', $contentDisposition);
        $this->assertStringNotContainsString('attachment', $contentDisposition);
    }
}



