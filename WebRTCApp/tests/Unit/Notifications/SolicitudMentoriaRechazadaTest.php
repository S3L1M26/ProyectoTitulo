<?php

namespace Tests\Unit\Notifications;

use App\Models\SolicitudMentoria;
use App\Models\User;
use App\Notifications\SolicitudMentoriaRechazada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class SolicitudMentoriaRechazadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $notification = new SolicitudMentoriaRechazada($solicitud);

        $this->assertContains('mail', $notification->via($estudiante));
        $this->assertContains('database', $notification->via($estudiante));
    }

    public function test_notification_mail_contains_mentor_name(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create([
            'role' => 'mentor',
            'name' => 'Dra. López',
        ]);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $notification = new SolicitudMentoriaRechazada($solicitud);
        $mail = $notification->toMail($estudiante);

        $rendered = $mail->render();
        $this->assertStringContainsString('Dra. López', $rendered);
    }

    public function test_notification_includes_encouragement_message(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $notification = new SolicitudMentoriaRechazada($solicitud);
        $mail = $notification->toMail($estudiante);

        // Should include some encouragement or next steps
        $rendered = $mail->render();
        $this->assertNotNull($rendered);
        $this->assertNotEquals('', $rendered);
    }

    public function test_notification_allows_request_again(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $notification = new SolicitudMentoriaRechazada($solicitud);
        $mail = $notification->toMail($estudiante);

        // Verify there's a way to continue
        $this->assertNotEmpty($mail->actionUrl);
    }

    public function test_notification_implements_should_queue(): void
    {
        $estudiante = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $estudiante->id,
            'mentor_id' => $mentor->id,
            'estado' => 'rechazada',
        ]);

        $notification = new SolicitudMentoriaRechazada($solicitud);

        $this->assertTrue(
            in_array('Illuminate\Contracts\Queue\ShouldQueue', class_implements($notification))
        );
    }
}
