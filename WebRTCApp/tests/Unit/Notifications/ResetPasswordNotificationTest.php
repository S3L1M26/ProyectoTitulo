<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

class ResetPasswordNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_notification_uses_queue()
    {
        $notification = new ResetPasswordNotification('test-token-123');
        
        $this->assertContains('Illuminate\Bus\Queueable', class_uses($notification));
    }

    public function test_notification_uses_mail_channel()
    {
        $user = new User(['email' => 'test@example.com', 'role' => 'student']);
        
        $notification = new ResetPasswordNotification('test-token');
        $channels = $notification->via($user);
        
        $this->assertContains('mail', $channels);
    }

    public function test_mail_message_has_subject()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $notification = new ResetPasswordNotification('test-token');
        $mailMessage = $notification->toMail($user);
        
        $this->assertEquals('Restablecer Contraseña', $mailMessage->subject);
    }

    public function test_mail_message_includes_greeting()
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'mentor'
        ]);
        
        $notification = new ResetPasswordNotification('token123');
        $mailMessage = $notification->toMail($user);
        
        $this->assertStringContainsString('¡Hola!', $mailMessage->render());
    }

    public function test_constructor_accepts_token()
    {
        $token = 'test-reset-token-abc123';
        $notification = new ResetPasswordNotification($token);
        
        $this->assertInstanceOf(ResetPasswordNotification::class, $notification);
    }

    public function test_mail_message_has_reset_action()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $notification = new ResetPasswordNotification('abc123token');
        $mailMessage = $notification->toMail($user);
        
        $this->assertNotNull($mailMessage->actionText);
        $this->assertNotNull($mailMessage->actionUrl);
        $this->assertEquals('Restablecer Contraseña', $mailMessage->actionText);
    }

    public function test_mail_message_includes_token_in_url()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $token = 'unique-token-xyz789';
        $notification = new ResetPasswordNotification($token);
        $mailMessage = $notification->toMail($user);
        
        $this->assertStringContainsString($token, $mailMessage->actionUrl);
    }

    public function test_mail_message_includes_email_in_url()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'testuser@example.com',
            'role' => 'mentor'
        ]);
        
        $notification = new ResetPasswordNotification('token123');
        $mailMessage = $notification->toMail($user);
        
        $this->assertStringContainsString(urlencode($user->email), $mailMessage->actionUrl);
    }

    public function test_mail_message_mentions_expiration()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $notification = new ResetPasswordNotification('token');
        $mailMessage = $notification->toMail($user);
        $content = $mailMessage->render();
        
        $this->assertStringContainsString('minutos', strtolower($content));
    }

    public function test_notification_implements_should_queue()
    {
        $reflection = new \ReflectionClass(ResetPasswordNotification::class);
        $interfaces = $reflection->getInterfaceNames();
        
        $this->assertContains('Illuminate\Contracts\Queue\ShouldQueue', $interfaces);
    }

    public function test_mail_message_has_proper_structure()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $notification = new ResetPasswordNotification('test-token');
        $mailMessage = $notification->toMail($user);
        
        $this->assertNotNull($mailMessage->subject);
        $this->assertIsArray($mailMessage->introLines);
        $this->assertNotEmpty($mailMessage->introLines);
    }

    public function test_mail_message_includes_security_warning()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role' => 'mentor'
        ]);
        
        $notification = new ResetPasswordNotification('token456');
        $mailMessage = $notification->toMail($user);
        $content = $mailMessage->render();
        
        // Verificar que contiene advertencia de seguridad
        $this->assertStringContainsString('no solicitaste', strtolower($content));
    }

    public function test_mail_message_includes_salutation()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $notification = new ResetPasswordNotification('test-token');
        $mailMessage = $notification->toMail($user);
        $content = $mailMessage->render();
        
        $this->assertStringContainsString('Saludos', $content);
    }
}
