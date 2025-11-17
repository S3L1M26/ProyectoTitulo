<?php

namespace Tests\Unit\Notifications;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Notifications\SolicitudMentoriaRecibida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class SolicitudMentoriaRecibidaTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $notification = new SolicitudMentoriaRecibida($solicitud);

        $this->assertContains('mail', $notification->via($mentor));
        $this->assertContains('database', $notification->via($mentor));
    }

    public function test_notification_mail_contains_required_information(): void
    {
        $estudiante = User::factory()->create([
            'role' => 'student',
            'name' => 'Juan Estudiante',
            'email' => 'juan@example.com',
        ]);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'mensaje' => 'Me gustaría solicitar mentoría en Laravel',
        ]);

        $notification = new SolicitudMentoriaRecibida($solicitud);
        $mail = $notification->toMail($mentor);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertStringContainsString('Juan Estudiante', $mail->render());
        $this->assertStringContainsString('Laravel', $mail->render());
    }

    public function test_notification_has_action_url(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $notification = new SolicitudMentoriaRecibida($solicitud);
        $mail = $notification->toMail($mentor);

        $this->assertNotEmpty($mail->actionUrl);
    }

    public function test_notification_implements_should_queue(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
        ]);

        $notification = new SolicitudMentoriaRecibida($solicitud);

        $this->assertTrue(
            in_array('Illuminate\Contracts\Queue\ShouldQueue', class_implements($notification))
        );
    }
}
