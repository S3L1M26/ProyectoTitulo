<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorReview>
 */
class MentorReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_id' => Mentor::factory(),
            'user_id' => User::factory(['role' => 'student']),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional(0.7)->text(200),
        ];
    }

    /**
     * Indicar que es una reseña con rating alto.
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
        ]);
    }

    /**
     * Indicar que es una reseña con rating bajo.
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicar que es una reseña con comentario.
     */
    public function withComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => $this->faker->text(200),
        ]);
    }

    /**
     * Indicar que es una reseña sin comentario.
     */
    public function withoutComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => null,
        ]);
    }
}
