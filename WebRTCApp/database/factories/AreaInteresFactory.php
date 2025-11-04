<?php

namespace Database\Factories;

use App\Models\AreaInteres;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AreaInteres>
 */
class AreaInteresFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AreaInteres::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $areas = [
            'PHP',
            'JavaScript',
            'Python',
            'Java',
            'Ruby',
            'C#',
            'Go',
            'Rust',
            'Laravel',
            'React',
            'Vue.js',
            'Angular',
            'Node.js',
            'Django',
            'Spring Boot',
            'Bases de Datos',
            'DevOps',
            'Machine Learning',
            'Ciberseguridad',
            'Desarrollo Móvil',
        ];

        return [
            'nombre' => fake()->unique()->randomElement($areas),
            'descripcion' => fake()->sentence(10),
        ];
    }

    /**
     * Create an area with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => $name,
        ]);
    }
}
