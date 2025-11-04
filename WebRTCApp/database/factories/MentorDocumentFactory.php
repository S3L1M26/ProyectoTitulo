<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\MentorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorDocument>
 */
class MentorDocumentFactory extends Factory
{
    protected $model = MentorDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mentor(),
            'file_path' => 'mentor_cvs/' . fake()->uuid() . '.pdf',
            'extracted_text' => null,
            'keyword_score' => 0, // Default 0 para pending documents
            'status' => 'pending',
            'processed_at' => null,
            'rejection_reason' => null,
            'is_public' => false,
        ];
    }

    /**
     * Indicate that the document is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'keyword_score' => fake()->numberBetween(60, 100),
            'extracted_text' => $this->generateTechnicalText(),
            'processed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the document is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'keyword_score' => 0,
            'extracted_text' => null,
            'processed_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the document is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'keyword_score' => fake()->numberBetween(0, 59),
            'extracted_text' => fake()->text(500),
            'processed_at' => now(),
            'rejection_reason' => fake()->randomElement([
                'El CV no contiene suficientes palabras clave técnicas',
                'Falta información sobre experiencia profesional',
                'No se detectaron tecnologías relevantes',
                'El documento no cumple con los requisitos mínimos',
            ]),
        ]);
    }

    /**
     * Indicate that the document is invalid.
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'invalid',
            'keyword_score' => 0,
            'extracted_text' => null,
            'processed_at' => now(),
            'rejection_reason' => 'Error al procesar el documento',
        ]);
    }

    /**
     * Indicate that the document is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the document is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Generate realistic technical CV text with keywords.
     */
    private function generateTechnicalText(): string
    {
        $templates = [
            'Ingeniero de Software con 5 años de experiencia en desarrollo fullstack usando PHP, Laravel, JavaScript, React y Node.js. Experiencia en bases de datos MySQL y PostgreSQL.',
            'Desarrollador Senior especializado en Python, Django, FastAPI y DevOps. Conocimientos en Docker, Kubernetes, AWS y CI/CD.',
            'Arquitecto de Software con expertise en Java, Spring Boot, microservicios y arquitecturas cloud-native. Certificado en AWS Solutions Architect.',
            'Full Stack Developer con dominio de TypeScript, Angular, Vue.js, Express.js y MongoDB. Experiencia en metodologías ágiles Scrum.',
            'Ingeniero DevOps especializado en automatización, Terraform, Ansible, Jenkins y GitLab CI. Experiencia con Kubernetes y contenedores.',
        ];

        return fake()->randomElement($templates);
    }
}
