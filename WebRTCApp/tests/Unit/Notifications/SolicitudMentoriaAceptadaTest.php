<?php

namespace Tests\Unit\Notifications;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Notifications\SolicitudMentoriaAceptada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class SolicitudMentoriaAceptadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $notification = new SolicitudMentoriaAceptada($solicitud);

        $this->assertContains('mail', $notification->via($estudiante));
        $this->assertContains('database', $notification->via($estudiante));
    }

    public function test_notification_mail_contains_mentor_name(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create([
            'role' => 'mentor',
            'name' => 'Dr. García',
        ]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $notification = new SolicitudMentoriaAceptada($solicitud);
        $mail = $notification->toMail($estudiante);

        $rendered = $mail->render();
        $this->assertStringContainsString('Dr. García', $rendered);
    }

    public function test_notification_has_action_to_view_details(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $notification = new SolicitudMentoriaAceptada($solicitud);
        $mail = $notification->toMail($estudiante);

        $this->assertNotEmpty($mail->actionUrl);
    }

    public function test_notification_implements_should_queue(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'aceptada',
        ]);

        $notification = new SolicitudMentoriaAceptada($solicitud);

        $this->assertTrue(
            in_array('Illuminate\Contracts\Queue\ShouldQueue', class_implements($notification))
        );
    }
}
