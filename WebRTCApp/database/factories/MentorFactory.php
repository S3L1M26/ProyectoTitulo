<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mentor>
 */
class MentorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mentor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experiencia' => fake()->paragraph(5), // ~100+ chars
            'biografia' => fake()->paragraph(10), // ~200+ chars
            'años_experiencia' => fake()->numberBetween(1, 20),
            'disponibilidad' => fake()->randomElement([
                'Lunes a Viernes 9-17h',
                'Fines de semana',
                'Horario flexible',
                'Tardes solamente'
            ]),
            'disponibilidad_detalle' => fake()->sentence(),
            'disponible_ahora' => fake()->boolean(70), // 70% true
            'calificacionPromedio' => fake()->randomFloat(1, 3.0, 5.0),
        ];
    }

    /**
     * Indicate that the mentor is currently available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'disponible_ahora' => true,
        ]);
    }

    /**
     * Indicate that the mentor is not currently available.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'disponible_ahora' => false,
        ]);
    }

    /**
     * Indicate that the mentor has a high rating.
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'calificacionPromedio' => fake()->randomFloat(1, 4.5, 5.0),
        ]);
    }

    /**
     * Indicate that the mentor has no rating.
     */
    public function noRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'calificacionPromedio' => null,
        ]);
    }

    /**
     * Indicate that the mentor has short fields (incomplete profile).
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'experiencia' => fake()->sentence(3), // <50 chars
            'biografia' => fake()->sentence(5), // <100 chars
            'años_experiencia' => 0,
            'disponibilidad' => null,
        ]);
    }
}
