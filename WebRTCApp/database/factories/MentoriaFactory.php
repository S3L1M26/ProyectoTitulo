<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Models\SolicitudMentoria;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mentoria>
 */
class MentoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fecha = fake()->dateTimeBetween('now', '+30 days');
        $hora = fake()->time('H:i:s');
        
        return [
            'solicitud_id' => SolicitudMentoria::factory(),
            'aprendiz_id' => User::factory()->state(['role' => 'student']),
            'mentor_id' => User::factory()->state(['role' => 'mentor']),
            'fecha' => $fecha,
            'hora' => $hora,
            'duracion_minutos' => fake()->randomElement([30, 45, 60, 90, 120]),
            'enlace_reunion' => 'https://zoom.us/j/' . fake()->numerify('###########') . '?pwd=' . fake()->bothify('??########'),
            'zoom_meeting_id' => fake()->numerify('###########'),
            'zoom_password' => fake()->bothify('???###'),
            'estado' => 'confirmada',
            'notas_mentor' => null,
            'notas_aprendiz' => null,
        ];
    }

    /**
     * Indicar que la mentoría está confirmada (estado por defecto).
     */
    public function confirmada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'confirmada',
        ]);
    }

    /**
     * Indicar que la mentoría ya fue completada.
     */
    public function completada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'completada',
            'fecha' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'notas_mentor' => fake()->optional(0.7)->paragraph(),
            'notas_aprendiz' => fake()->optional(0.5)->paragraph(),
        ]);
    }

    /**
     * Indicar que la mentoría fue cancelada.
     */
    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cancelada',
        ]);
    }

    /**
     * Crear mentoría para hoy.
     */
    public function hoy(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => now()->toDateString(),
            'hora' => fake()->time('H:i:s'),
        ]);
    }

    /**
     * Crear mentoría próxima (dentro de 7 días).
     */
    public function proxima(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Crear mentoría sin enlace de Zoom (manual).
     */
    public function sinEnlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'enlace_reunion' => null,
            'zoom_meeting_id' => null,
            'zoom_password' => null,
        ]);
    }
}
