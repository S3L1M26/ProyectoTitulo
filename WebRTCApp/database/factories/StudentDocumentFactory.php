<?php

namespace Database\Factories;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'student']),
            'file_path' => 'student_certificates/' . $this->faker->uuid() . '.pdf',
            'extracted_text' => $this->faker->paragraph(5),
            'keyword_score' => $this->faker->numberBetween(0, 100),
            'status' => 'pending',
            'processed_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Indicate that the document is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'keyword_score' => $this->faker->numberBetween(50, 100),
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
            'keyword_score' => $this->faker->numberBetween(0, 49),
            'processed_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the document is invalid.
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'invalid',
            'processed_at' => now(),
            'rejection_reason' => 'Error al procesar el certificado',
        ]);
    }
}
