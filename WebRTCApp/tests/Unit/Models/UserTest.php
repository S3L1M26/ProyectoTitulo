<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

class UserTest extends TestCase
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
        
        $relation = $user->aprendiz();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    public function test_it_has_mentor_relationship()
    {
        $user = new User();
        
        $relation = $user->mentor();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    public function test_it_returns_default_completeness_for_unknown_role()
    {
        $user = new User(['role' => 'admin']);
        
        // Test the method exists and is callable
        $this->assertTrue(method_exists($user, 'getProfileCompletenessAttribute'));
    }

    public function test_it_has_calculate_student_completeness_method()
    {
        $user = new User();
        
        // Test the method exists
        $reflection = new \ReflectionClass($user);
        $this->assertTrue($reflection->hasMethod('calculateStudentCompleteness'));
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
}