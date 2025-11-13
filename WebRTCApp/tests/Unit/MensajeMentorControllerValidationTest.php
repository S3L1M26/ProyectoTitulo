<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MensajeMentorControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_fails_when_subject_or_message_missing()
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $student = User::factory()->create(['role' => 'student']);

        // Create accepted solicitud to pass authorization check
        DB::table('solicitud_mentorias')->insert([
            'mentor_id' => $mentor->id,
            'estudiante_id' => $student->id,
            'estado' => 'aceptada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->post(route('student.mentores.contactar', $mentor->id), []);

        $response->assertSessionHasErrors(['asunto', 'mensaje']);
    }

    public function test_validation_fails_when_fields_exceed_limits()
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $student = User::factory()->create(['role' => 'student']);

        // Create accepted solicitud to pass authorization check
        DB::table('solicitud_mentorias')->insert([
            'mentor_id' => $mentor->id,
            'estudiante_id' => $student->id,
            'estado' => 'aceptada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $longSubject = str_repeat('A', 200);
        $longMessage = str_repeat('B', 3000);

        $response = $this->actingAs($student)->post(route('student.mentores.contactar', $mentor->id), [
            'asunto' => $longSubject,
            'mensaje' => $longMessage,
        ]);

        $response->assertSessionHasErrors(['asunto', 'mensaje']);
    }
}
