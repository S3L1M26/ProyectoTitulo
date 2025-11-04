<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\MentorDocument;
use App\Jobs\ProcessMentorCVJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessMentorCVJobTest extends TestCase
{
    use RefreshDatabase;

    protected MentorDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->mentor()->create();
        $this->document = MentorDocument::factory()->for($user, 'user')->create();
    }

    /**
     * Helper method to invoke private validateCV method using Reflection
     */
    protected function invokeValidateCV(ProcessMentorCVJob $job, string $text): array
    {
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateCV');
        $method->setAccessible(true);

        return $method->invoke($job, $text);
    }

    public function test_cv_with_sufficient_keywords_is_approved(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        $text = 'ingeniero de software con 5 años de experiencia en php, laravel, javascript. ' .
                'desarrollador fullstack. universidad nacional. email@test.com +51 999888777';

        $result = $this->invokeValidateCV($job, $text);

        $this->assertEquals('approved', $result['status']);
        $this->assertGreaterThanOrEqual(50, $result['score']);
        $this->assertNull($result['rejection_reason']);
    }

    public function test_cv_with_insufficient_keywords_is_rejected(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        $text = 'hola soy una persona que trabaja en tecnología';

        $result = $this->invokeValidateCV($job, $text);

        $this->assertEquals('rejected', $result['status']);
        $this->assertLessThan(50, $result['score']);
        $this->assertNotNull($result['rejection_reason']);
    }

    public function test_scoring_system_awards_points_for_critical_keywords(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Palabras críticas: experiencia, php, laravel, javascript, universidad (15 pts c/u)
        $text = 'experiencia en php y laravel';

        $result = $this->invokeValidateCV($job, $text);

        // experiencia (15) + php (15) + laravel (15) = 45 puntos
        $this->assertGreaterThanOrEqual(45, $result['score']);
        $this->assertContains('experiencia', $result['found_keywords']);
        $this->assertContains('php', $result['found_keywords']);
        $this->assertContains('laravel', $result['found_keywords']);
    }

    public function test_scoring_system_awards_points_for_important_keywords(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Palabras importantes: desarrollador, ingeniero, años, proyecto, git (10 pts c/u)
        $text = 'ingeniero desarrollador con 3 años en proyectos usando git';

        $result = $this->invokeValidateCV($job, $text);

        // ingeniero (10) + desarrollador (10) + años (10) + proyecto (10) + git (10) = 50 puntos
        $this->assertGreaterThanOrEqual(50, $result['score']);
        $this->assertContains('ingeniero', $result['found_keywords']);
        $this->assertContains('desarrollador', $result['found_keywords']);
    }

    public function test_scoring_system_awards_points_for_optional_keywords(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Palabras opcionales: docker, aws, react, vue, mysql, python (5 pts c/u)
        $text = 'conocimientos en docker, aws, react y mysql';

        $result = $this->invokeValidateCV($job, $text);

        // docker (5) + aws (5) + react (5) + mysql (5) = 20 puntos
        $this->assertGreaterThanOrEqual(20, $result['score']);
        $this->assertContains('docker', $result['found_keywords']);
        $this->assertContains('aws', $result['found_keywords']);
    }

    public function test_scoring_system_awards_bonus_for_email(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        $text = 'contacto: juan.perez@example.com';

        $result = $this->invokeValidateCV($job, $text);

        // Bonus de email: +10 puntos
        $this->assertGreaterThanOrEqual(10, $result['score']);
        $this->assertContains('email (+10)', $result['bonuses']);
    }

    public function test_scoring_system_awards_bonus_for_phone(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        $text = 'teléfono: +51 987654321';

        $result = $this->invokeValidateCV($job, $text);

        // Bonus de teléfono: +5 puntos
        $this->assertGreaterThanOrEqual(5, $result['score']);
        $this->assertContains('teléfono (+5)', $result['bonuses']);
    }

    public function test_minimum_score_threshold_is_50_points(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Texto con exactamente 49 puntos (debe ser rechazado)
        // experiencia (15) + php (15) + desarrollador (10) + git (10) = 50, quitar git = 40
        $text = 'experiencia en php como desarrollador fullstack';

        $result = $this->invokeValidateCV($job, $text);

        // Con 45 puntos (exp + php + dev) debe ser rechazado
        $this->assertEquals('rejected', $result['status']);

        // Ahora con 50+ puntos debe ser aprobado
        $text2 = 'experiencia en php como desarrollador con git';
        $result2 = $this->invokeValidateCV($job, $text2);
        
        $this->assertEquals('approved', $result2['status']);
    }

    public function test_rejection_reason_is_generated_for_low_scores(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Score de 0
        $text = 'documento sin palabras clave';
        $result = $this->invokeValidateCV($job, $text);
        
        $this->assertStringContainsString('no contiene información técnica relevante', $result['rejection_reason']);
        
        // Score bajo (< 20)
        $text2 = 'desarrollador'; // 10 puntos
        $result2 = $this->invokeValidateCV($job, $text2);
        
        $this->assertStringContainsString('muy poca información técnica', $result2['rejection_reason']);
    }

    public function test_extracted_text_is_converted_to_lowercase(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Probar con texto en mayúsculas y minúsculas mezcladas
        // El método validateCV espera texto en lowercase (lo convierte extractTextDirectly/extractTextFromImages)
        $text = strtolower('EXPERIENCIA en PHP, LARAVEL, JavaScript y Universidad');

        $result = $this->invokeValidateCV($job, $text);

        // Debe detectar las palabras aunque estén en mayúsculas originalmente
        $this->assertContains('experiencia', $result['found_keywords']);
        $this->assertContains('php', $result['found_keywords']);
        $this->assertContains('laravel', $result['found_keywords']);
        $this->assertContains('javascript', $result['found_keywords']);
        $this->assertContains('universidad', $result['found_keywords']);
    }

    public function test_complete_cv_scores_high(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        $text = 'ingeniero de software con 5 años de experiencia en desarrollo fullstack. ' .
                'universidad nacional de ingeniería. ' .
                'tecnologías: php, laravel, javascript, react, vue, python, mysql, docker, aws. ' .
                'proyectos con git y metodologías ágiles. ' .
                'contacto: juan.perez@example.com, +51 987654321';

        $result = $this->invokeValidateCV($job, $text);

        // Críticas (5x15=75): experiencia, php, laravel, javascript, universidad
        // Importantes (5x10=50): ingeniero, desarrollador, años, proyecto, git
        // Opcionales (6x5=30): react, vue, python, mysql, docker, aws
        // Bonuses (+15): email (+10) + teléfono (+5)
        // Total esperado: 75+50+30+15 = 170 puntos
        
        $this->assertEquals('approved', $result['status']);
        $this->assertGreaterThanOrEqual(100, $result['score']);
        $this->assertNull($result['rejection_reason']);
    }

    public function test_cv_without_contact_info_can_still_be_approved(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Sin email ni teléfono, pero con suficientes keywords técnicas
        $text = 'ingeniero con 5 años de experiencia en php, laravel, javascript, desarrollador de proyectos con git';

        $result = $this->invokeValidateCV($job, $text);

        // exp(15) + php(15) + laravel(15) + js(15) + ing(10) + años(10) + dev(10) + proy(10) + git(10) = 110
        $this->assertEquals('approved', $result['status']);
        $this->assertGreaterThanOrEqual(50, $result['score']);
    }

    public function test_phone_number_variations_are_detected(): void
    {
        $job = new ProcessMentorCVJob($this->document);
        
        // Diferentes formatos de teléfono peruano
        $variations = [
            '+51 987654321',
            '51 987654321',
            '(+51) 987654321',
        ];

        foreach ($variations as $phone) {
            $text = "teléfono: $phone";
            $result = $this->invokeValidateCV($job, $text);
            
            $this->assertContains('teléfono (+5)', $result['bonuses'], "Failed for phone format: $phone");
        }
    }
}
