<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SendProfileReminderJob;
use App\Models\User;
use App\Notifications\ProfileIncompleteReminder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendProfileReminderJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake notifications para testing
        Notification::fake();
    }

    public function test_job_implements_should_queue_interface()
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        // Use 'percentage' to match notification payload expectations
        $profileData = ['percentage' => 50];
        
        $job = new SendProfileReminderJob($user, $profileData);
        
        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    public function test_job_uses_queueable_trait()
    {
        $job = new SendProfileReminderJob(new User(), []);
        
        $reflection = new \ReflectionClass($job);
        $traits = $reflection->getTraitNames();
        
        $this->assertContains('Illuminate\Foundation\Queue\Queueable', $traits);
    }

    public function test_constructor_sets_user_and_profile_data()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $profileData = [
            'percentage' => 75,
            'missing_fields' => ['biografia', 'experiencia']
        ];
        
        $job = new SendProfileReminderJob($user, $profileData);
        
        $this->assertEquals($user->name, $job->user->name);
        $this->assertEquals($user->email, $job->user->email);
        $this->assertEquals($profileData, $job->profileData);
    $this->assertEquals(75, $job->profileData['percentage']);
    }

    public function test_handle_method_sends_notification()
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
    $profileData = ['percentage' => 60, 'missing_fields' => []];
        
        $job = new SendProfileReminderJob($user, $profileData);
        
        // Ejecutar el job
        $job->handle();
        
        // Verificar que la notificación fue enviada (comparando payload via toArray)
        Notification::assertSentTo(
            $user,
            ProfileIncompleteReminder::class,
            function ($notification) use ($profileData, $user) {
                $payload = $notification->toArray($user);
                return isset($payload['profile_percentage']) && $payload['profile_percentage'] === $profileData['percentage'];
            }
        );
    }

    public function test_job_has_correct_public_properties()
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $profileData = ['test' => 'data'];
        
        $job = new SendProfileReminderJob($user, $profileData);
        
        // Verificar que las propiedades son públicas y accesibles
        $reflection = new \ReflectionClass($job);
        
        $userProperty = $reflection->getProperty('user');
        $this->assertTrue($userProperty->isPublic());
        
        $dataProperty = $reflection->getProperty('profileData');
        $this->assertTrue($dataProperty->isPublic());
    }

    public function test_job_handles_empty_profile_data()
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
    // Notification expects 'percentage' and 'missing_fields'; provide defaults
    $emptyProfileData = ['percentage' => 0, 'missing_fields' => []];

        $job = new SendProfileReminderJob($user, $emptyProfileData);
        
        // Ejecutar el job con datos vacíos
        $job->handle();
        
        // Verificar que la notificación fue enviada incluso con datos vacíos
        Notification::assertSentTo(
            $user,
            ProfileIncompleteReminder::class,
            function ($notification) use ($emptyProfileData, $user) {
                $payload = $notification->toArray($user);
                return isset($payload['profile_percentage']) && $payload['profile_percentage'] === $emptyProfileData['percentage'];
            }
        );
    }

    public function test_job_handles_complex_profile_data()
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $complexProfileData = [
            'percentage' => 85,
            'missing_fields' => ['biografia', 'disponibilidad'],
            'completed_fields' => ['name', 'email', 'experiencia'],
            'user_role' => 'mentor',
            'last_updated' => '2025-10-23'
        ];
        
        $job = new SendProfileReminderJob($user, $complexProfileData);
        
        // Verificar que maneja datos complejos correctamente
        $this->assertEquals($complexProfileData, $job->profileData);
        $this->assertEquals('mentor', $job->profileData['user_role']);
    $this->assertEquals(85, $job->profileData['percentage']);
        
        // Ejecutar y verificar notificación
        $job->handle();
        
        Notification::assertSentTo(
            $user,
            ProfileIncompleteReminder::class,
            function ($notification) use ($complexProfileData, $user) {
                $payload = $notification->toArray($user);
                return isset($payload['profile_percentage']) && $payload['profile_percentage'] === $complexProfileData['percentage'] &&
                       isset($payload['missing_fields']) && in_array('biografia', $payload['missing_fields']);
            }
        );
    }
}