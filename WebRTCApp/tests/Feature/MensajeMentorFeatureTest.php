<?php

namespace Tests\Feature;

use App\Mail\MensajeMentorMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MensajeMentorFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_send_message_to_authorized_mentor_and_mail_sent()
    {
        Mail::fake();

        $mentor = User::factory()->create(['role' => 'mentor']);
        $student = User::factory()->create(['role' => 'student']);

        // insert accepted solicitud to allow contact
        DB::table('solicitud_mentorias')->insert([
            'mentor_id' => $mentor->id,
            'estudiante_id' => $student->id,
            'estado' => 'aceptada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->post(route('student.mentores.contactar', $mentor->id), [
            'asunto' => 'Consulta previa',
            'mensaje' => 'Hola, quisiera confirmar algunos detalles antes de la sesión.'
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        Mail::assertSent(MensajeMentorMail::class, function ($mail) use ($mentor) {
            return $mail->hasTo($mentor->email);
        });
    }

    public function test_student_cannot_contact_unrelated_mentor()
    {
        Mail::fake();

        $mentor = User::factory()->create(['role' => 'mentor']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->post(route('student.mentores.contactar', $mentor->id), [
            'asunto' => 'No autorizado',
            'mensaje' => 'Intento de contacto'
        ]);

        $response->assertSessionHasErrors('contacto');
        Mail::assertNothingSent();
    }
}
