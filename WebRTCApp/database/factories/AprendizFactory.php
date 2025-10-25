<?php

namespace Database\Factories;

use App\Models\Aprendiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Aprendiz>
 */
class AprendizFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Aprendiz::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'semestre' => fake()->numberBetween(1, 10),
            'objetivos' => fake()->sentence(10),
        ];
    }

    /**
     * Indicate that the aprendiz has no semestre.
     */
    public function withoutSemestre(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => null,
        ]);
    }

    /**
     * Indicate that the aprendiz has no objetivos.
     */
    public function withoutObjetivos(): static
    {
        return $this->state(fn (array $attributes) => [
            'objetivos' => null,
        ]);
    }

    /**
     * Indicate that the aprendiz has a complete profile.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => fake()->numberBetween(1, 10),
            'objetivos' => fake()->sentence(15),
        ]);
    }
}
