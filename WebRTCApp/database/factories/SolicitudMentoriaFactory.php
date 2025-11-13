<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\SolicitudMentoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolicitudMentoria>
 */
class SolicitudMentoriaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SolicitudMentoria::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estudiante_id' => User::factory(),
            'mentor_id' => User::factory(),
            'mensaje' => fake()->paragraph(3),
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
            'fecha_respuesta' => null,
        ];
    }

    /**
     * Indicate that the request is pending.
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
            'fecha_respuesta' => null,
        ]);
    }

    /**
     * Indicate that the request has been accepted.
     */
    public function aceptada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'aceptada',
            'fecha_respuesta' => now(),
        ]);
    }

    /**
     * Indicate that the request has been rejected.
     */
    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'rechazada',
            'fecha_respuesta' => now(),
        ]);
    }
}
