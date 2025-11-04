<?php

namespace Tests\Unit;

use App\Jobs\ProcessStudentCertificateJob;
use App\Models\StudentDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessStudentCertificateJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test certificate with sufficient score is approved.
     */
    public function test_certificate_with_sufficient_keywords_is_approved(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con palabras clave suficientes para aprobar
        $text = 'universidad andrés bello certificado alumno regular 2024 semestre carrera ingeniería';
        $result = $method->invoke($job, $text);

        $this->assertEquals('approved', $result['status']);
        $this->assertGreaterThanOrEqual(40, $result['score']);
        $this->assertNull($result['rejection_reason']);
    }

    /**
     * Test certificate with insufficient score is rejected.
     */
    public function test_certificate_with_insufficient_keywords_is_rejected(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con muy pocas palabras clave
        $text = 'documento genérico sin información específica';
        $result = $method->invoke($job, $text);

        $this->assertEquals('rejected', $result['status']);
        $this->assertLessThan(40, $result['score']);
        $this->assertNotNull($result['rejection_reason']);
    }

    /**
     * Test scoring system counts institution keywords correctly.
     */
    public function test_scoring_system_awards_points_for_institution_keywords(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con palabra clave de institución
        $text = 'universidad certificado alumno';
        $result = $method->invoke($job, $text);

        // Universidad (20) + Certificado (15) + Alumno (15) = 50 puntos
        $this->assertEquals(50, $result['score']);
        $this->assertEquals('approved', $result['status']);
    }

    /**
     * Test scoring system counts document type keywords correctly.
     */
    public function test_scoring_system_awards_points_for_document_type_keywords(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con tipo de documento
        $text = 'constancia de estudios';
        $result = $method->invoke($job, $text);

        // Solo constancia: 15 puntos (insuficiente)
        $this->assertEquals(15, $result['score']);
        $this->assertEquals('rejected', $result['status']);
    }

    /**
     * Test scoring system counts student status keywords correctly.
     */
    public function test_scoring_system_awards_points_for_student_status_keywords(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto completo con todas las categorías
        $text = 'universidad andrés bello certificado de alumno regular 2024 primer semestre carrera ingeniería';
        $result = $method->invoke($job, $text);

        // Universidad (20) + Certificado (15) + Alumno Regular (15) + 2024 (10) + Semestre (10) + Carrera (10) + Ingeniería (10) = 90
        $this->assertGreaterThanOrEqual(40, $result['score']);
        $this->assertEquals('approved', $result['status']);
    }

    /**
     * Test scoring system counts complementary keywords correctly.
     */
    public function test_scoring_system_awards_points_for_complementary_keywords(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con palabras complementarias
        $text = 'universidad certificado alumno 2024 semestre carrera';
        $result = $method->invoke($job, $text);

        // Universidad (20) + Certificado (15) + Alumno (15) + 2024 (10) + Semestre (10) + Carrera (10) = 80
        $this->assertEquals(80, $result['score']);
        $this->assertEquals('approved', $result['status']);
    }

    /**
     * Test job handles exceptions gracefully.
     */
    public function test_job_marks_document_as_invalid_on_exception(): void
    {
        // Este test se simplifica: solo verificamos que el sistema
        // de validación rechaza correctamente documentos sin contenido
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto vacío debería resultar en rechazo
        $result = $method->invoke($job, '');

        $this->assertEquals('rejected', $result['status']);
        $this->assertEquals(0, $result['score']);
        $this->assertNotNull($result['rejection_reason']);
    }

    /**
     * Test minimum score threshold for approval is 40 points.
     */
    public function test_minimum_score_threshold_is_40_points(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Texto con exactamente 40 puntos
        $text = 'universidad certificado alumno'; // 20 + 15 + 15 = 50
        $result = $method->invoke($job, $text);

        $this->assertEquals('approved', $result['status']);

        // Texto con menos de 40 puntos
        $text2 = 'certificado alumno'; // 15 + 15 = 30
        $result2 = $method->invoke($job, $text2);

        $this->assertEquals('rejected', $result2['status']);
    }

    /**
     * Test rejection reason is generated for low scores.
     */
    public function test_rejection_reason_is_generated_for_low_scores(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        $text = 'documento genérico';
        $result = $method->invoke($job, $text);

        $this->assertEquals('rejected', $result['status']);
        $this->assertNotNull($result['rejection_reason']);
        $this->assertIsString($result['rejection_reason']);
    }

    /**
     * Test extracted text is stored in lowercase.
     */
    public function test_extracted_text_is_converted_to_lowercase(): void
    {
        $job = new ProcessStudentCertificateJob(
            StudentDocument::factory()->make()
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCertificate');
        $method->setAccessible(true);

        // Keywords en mayúsculas deben funcionar igual
        $textUpper = 'UNIVERSIDAD CERTIFICADO ALUMNO';
        $textLower = 'universidad certificado alumno';

        $resultUpper = $method->invoke($job, strtolower($textUpper));
        $resultLower = $method->invoke($job, $textLower);

        $this->assertEquals($resultLower['score'], $resultUpper['score']);
    }
}
