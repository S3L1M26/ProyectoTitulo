<?php

namespace Tests\Feature\Jobs;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use App\Models\AreaInteres;
use App\Jobs\SendProfileReminderJob;
use App\Notifications\ProfileIncompleteReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class SendProfileReminderJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_sends_profile_reminder_notification_to_user()
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->withoutSemestre()->for($student)->create();

        $profileData = [
            'percentage' => 25,
            'missing_fields' => ['Semestre actual', 'Áreas de especialidad']
        ];

        $job = new SendProfileReminderJob($student, $profileData);
        $job->handle();

        Notification::assertSentTo(
            $student,
            ProfileIncompleteReminder::class,
            function ($notification) use ($profileData) {
                return $notification->toArray($notification) === [
                    'profile_percentage' => $profileData['percentage'],
                    'missing_fields' => $profileData['missing_fields'],
                ];
            }
        );
    }

    #[Test]
    public function job_can_be_dispatched_to_queue()
    {
        Queue::fake();

        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->incomplete()->for($mentor)->create();

        $profileData = [
            'percentage' => 50,
            'missing_fields' => ['Experiencia profesional detallada']
        ];

        SendProfileReminderJob::dispatch($mentor, $profileData);

        Queue::assertPushed(SendProfileReminderJob::class, function ($job) use ($mentor, $profileData) {
            return $job->user->id === $mentor->id &&
                   $job->profileData === $profileData;
        });
    }

    #[Test]
    public function notification_contains_correct_student_profile_data()
    {
        Notification::fake();

        $student = User::factory()->student()->create(['name' => 'Juan Pérez']);
        $aprendiz = Aprendiz::factory()->withoutSemestre()->withoutObjetivos()->for($student)->create();

        $profileData = [
            'percentage' => 25,
            'missing_fields' => ['Semestre actual', 'Objetivos de aprendizaje', 'Áreas de especialidad']
        ];

        $job = new SendProfileReminderJob($student, $profileData);
        $job->handle();

        Notification::assertSentTo(
            $student,
            ProfileIncompleteReminder::class,
            function ($notification) use ($student, $profileData) {
                $mailMessage = $notification->toMail($student);
                
                // Verificar el contenido del email
                $this->assertStringContainsString('¡Hola Juan Pérez!', $mailMessage->greeting);
                $this->assertStringContainsString('25%', $mailMessage->introLines[0]);
                $this->assertStringContainsString('mejores recomendaciones de mentores', $mailMessage->introLines[1]);
                
                // Verificar campos faltantes
                $this->assertStringContainsString('Semestre actual', implode(' ', $mailMessage->introLines));
                $this->assertStringContainsString('Objetivos de aprendizaje', implode(' ', $mailMessage->introLines));
                $this->assertStringContainsString('Áreas de especialidad', implode(' ', $mailMessage->introLines));
                
                return true;
            }
        );
    }

    #[Test]
    public function notification_contains_correct_mentor_profile_data()
    {
        Notification::fake();

        $mentor = User::factory()->mentor()->create(['name' => 'María González']);
        $mentorProfile = Mentor::factory()->incomplete()->for($mentor)->create();

        $profileData = [
            'percentage' => 60,
            'missing_fields' => ['Experiencia profesional', 'Biografía personal']
        ];

        $job = new SendProfileReminderJob($mentor, $profileData);
        $job->handle();

        Notification::assertSentTo(
            $mentor,
            ProfileIncompleteReminder::class,
            function ($notification) use ($mentor, $profileData) {
                $mailMessage = $notification->toMail($mentor);
                
                // Verificar el contenido del email
                $this->assertStringContainsString('¡Hola María González!', $mailMessage->greeting);
                $this->assertStringContainsString('60%', $mailMessage->introLines[0]);
                $this->assertStringContainsString('atraer más estudiantes', $mailMessage->introLines[1]);
                
                // Verificar campos faltantes
                $this->assertStringContainsString('Experiencia profesional', implode(' ', $mailMessage->introLines));
                $this->assertStringContainsString('Biografía personal', implode(' ', $mailMessage->introLines));
                
                return true;
            }
        );
    }

    #[Test]
    public function notification_has_correct_action_url()
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->for($student)->create();

        $profileData = [
            'percentage' => 75,
            'missing_fields' => ['Áreas de especialidad']
        ];

        $job = new SendProfileReminderJob($student, $profileData);
        $job->handle();

        Notification::assertSentTo(
            $student,
            ProfileIncompleteReminder::class,
            function ($notification) use ($student) {
                $mailMessage = $notification->toMail($student);
                
                // Verificar que tiene un botón de acción
                $this->assertNotEmpty($mailMessage->actionText);
                $this->assertNotEmpty($mailMessage->actionUrl);
                $this->assertEquals('Completar Perfil', $mailMessage->actionText);
                
                return true;
            }
        );
    }

    #[Test]
    public function job_handles_user_with_complete_profile()
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $aprendiz = Aprendiz::factory()->complete()->for($student)->create();
        $areas = AreaInteres::factory()->count(3)->create();
        $aprendiz->areasInteres()->attach($areas);

        // Aunque el perfil está completo, el job debería enviar la notificación
        $profileData = [
            'percentage' => 100,
            'missing_fields' => []
        ];

        $job = new SendProfileReminderJob($student, $profileData);
        $job->handle();

        Notification::assertSentTo(
            $student,
            ProfileIncompleteReminder::class
        );
    }

    #[Test]
    public function job_preserves_profile_data_through_queue()
    {
        $mentor = User::factory()->mentor()->create(['name' => 'Carlos Rodríguez']);
        $mentorProfile = Mentor::factory()->incomplete()->for($mentor)->create();

        $profileData = [
            'percentage' => 45,
            'missing_fields' => ['Experiencia profesional detallada', 'Biografía personal', 'Años de experiencia']
        ];

        // Disparar el job de forma síncrona en modo testing
        Notification::fake();
        
        SendProfileReminderJob::dispatchSync($mentor, $profileData);

        Notification::assertSentTo(
            $mentor,
            ProfileIncompleteReminder::class,
            function ($notification) use ($profileData) {
                $arrayData = $notification->toArray($notification);
                
                return $arrayData['profile_percentage'] === $profileData['percentage'] &&
                       $arrayData['missing_fields'] === $profileData['missing_fields'];
            }
        );
    }
}
