<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $user = new User();

        $expected = ['name', 'email', 'password', 'role', 'is_active'];
        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_it_has_correct_hidden_attributes()
    {
        $user = new User();

        $expected = ['password', 'remember_token'];
        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_relationship_methods_exist()
    {
        $user = new User();

        $this->assertTrue(method_exists($user, 'aprendiz'));
        $this->assertTrue(method_exists($user, 'mentor'));
    }

    public function test_calculate_student_completeness_method_exists()
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'calculateStudentCompleteness') || method_exists($user, 'getProfileCompletenessAttribute'));
    }

    public function test_profile_completion_field_validation()
    {
        $user = new User();
        $requiredFields = ['name', 'email'];

        foreach ($requiredFields as $field) {
            $this->assertContains($field, $user->getFillable());
        }
    }

    public function test_password_reset_notification_method_exists()
    {
        $user = new User(['email' => 'test@example.com']);
        $this->assertTrue(method_exists($user, 'sendPasswordResetNotification'));
    }

    public function test_role_attribute_can_be_assigned()
    {
        $user = new User(['role' => 'admin']);
        $this->assertEquals('admin', $user->role);
        
        $studentUser = new User(['role' => 'student']);
        $this->assertEquals('student', $studentUser->role);
        
        $mentorUser = new User(['role' => 'mentor']);
        $this->assertEquals('mentor', $mentorUser->role);
    }
    
    public function test_name_and_email_are_fillable()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }
    
    public function test_password_is_hidden_in_array_conversion()
    {
        $user = new User([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'secret123'
        ]);
        
        $array = $user->toArray();
        $this->assertArrayNotHasKey('password', $array);
    }
    
    public function test_remember_token_is_hidden_in_array_conversion()
    {
        $user = new User(['email' => 'test@example.com']);
        $user->remember_token = 'test_token_123';
        
        $array = $user->toArray();
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
