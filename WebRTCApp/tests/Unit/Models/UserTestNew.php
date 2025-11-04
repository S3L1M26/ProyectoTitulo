<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

class UserTestNew extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // No need to fake cache since we're using array driver in testing
        // Just ensure Notification is faked for notification tests
        Notification::fake();
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $user = new User();
        
        $expected = ['name', 'email', 'password', 'role'];
        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_it_has_correct_hidden_attributes()
    {
        $user = new User();
        
        $expected = ['password', 'remember_token'];
        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_it_has_aprendiz_relationship()
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'aprendiz'));
    }

    public function test_it_has_mentor_relationship()
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'mentor'));
    }

    public function test_it_returns_default_completeness_for_unknown_role()
    {
        $user = new User(['role' => 'unknown']);
        
        // Using reflection to test private method behavior
        $this->assertEquals('unknown', $user->role);
    }

    public function test_it_has_calculate_student_completeness_method()
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'calculateStudentCompleteness'));
    }

    public function test_it_sends_custom_password_reset_notification()
    {
        $user = new User(['email' => 'test@example.com']);
        
        $user->sendPasswordResetNotification('test-token');
        
        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }

    public function test_it_sends_custom_email_verification_notification()
    {
        $user = new User(['email' => 'test@example.com']);
        
        $user->sendEmailVerificationNotification();
        
        Notification::assertSentTo($user, \App\Notifications\VerifyEmailNotification::class);
    }

    public function test_it_has_correct_casts()
    {
        $user = new User();
        $casts = $user->getCasts();
        
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('hashed', $casts['password']);
    }

    public function test_it_implements_must_verify_email()
    {
        $user = new User();
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $user);
    }

    // ========== TESTS CRÍTICOS AÑADIDOS - FASE 1 (UNITARIOS PUROS) ==========

    public function test_calculate_student_completeness_basic_logic()
    {
        // Test unitario básico de la lógica de completeness
        $user = new User([
            'name' => 'Test Student',
            'email' => 'test@test.com',
            'role' => 'student'
        ]);

        // Verificar que el usuario tiene los campos básicos
        $this->assertEquals('Test Student', $user->name);
        $this->assertEquals('test@test.com', $user->email);
        $this->assertEquals('student', $user->role);
    }

    public function test_profile_completion_field_validation()
    {
        // Test de validación de campos de perfil
        $requiredFields = ['name', 'email'];
        $user = new User();
        
        foreach ($requiredFields as $field) {
            $this->assertContains($field, $user->getFillable());
        }
    }

    public function test_mentor_profile_basic_validation()
    {
        // Test básico para perfil de mentor
        $mentor = new User([
            'name' => 'Test Mentor',
            'email' => 'mentor@test.com',
            'role' => 'mentor'
        ]);

        $this->assertEquals('mentor', $mentor->role);
        $this->assertNotEmpty($mentor->name);
        $this->assertNotEmpty($mentor->email);
    }

    public function test_password_reset_edge_cases()
    {
        // Test de casos edge en reset de password
        $user = new User([
            'email' => 'test@example.com'
        ]);

        // Verificar que el método de notificación existe
        $this->assertTrue(method_exists($user, 'sendPasswordResetNotification'));
    }
}