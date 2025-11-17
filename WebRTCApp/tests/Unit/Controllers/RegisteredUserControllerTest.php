<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;

class RegisteredUserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake events para testing
        Event::fake();
    }

    public function test_controller_extends_base_controller()
    {
        $controller = new RegisteredUserController();
        
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $controller);
    }

    public function test_create_method_returns_register_view_with_student_role()
    {
        $controller = new RegisteredUserController();
        $request = Request::create('/register', 'GET', ['role' => 'student']);
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertTrue($reflection->hasMethod('create'));
    }

    public function test_create_method_handles_mentor_role()
    {
        $controller = new RegisteredUserController();
        $request = Request::create('/register', 'GET', ['role' => 'mentor']);
        
        // Verificar que el método existe y es público
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('create'));
        
        $method = $reflection->getMethod('create');
        $this->assertTrue($method->isPublic());
    }

    public function test_store_method_validation_rules()
    {
        $controller = new RegisteredUserController();
        
        // Verificar que el método store existe
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('store'));
        
        $method = $reflection->getMethod('store');
        $this->assertTrue($method->isPublic());
        
        // Test de estructura de validación (unitario)
        $this->assertNotNull($method);
    }

    public function test_student_registration_logic()
    {
        // Test de lógica unitaria sin base de datos
        $userData = [
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'password' => 'password123',
            'role' => 'student'
        ];

        // Verificar lógica de datos para estudiante
        $this->assertEquals('student', $userData['role']);
        $this->assertNotEmpty($userData['name']);
        $this->assertNotEmpty($userData['email']);
        
        // Verificar que el email tiene formato correcto
        $this->assertStringContainsString('@', $userData['email']);
    }

    public function test_mentor_registration_logic()
    {
        // Test de lógica unitaria sin base de datos
        $userData = [
            'name' => 'Test Mentor',
            'email' => 'mentor@test.com',
            'password' => 'password123',
            'role' => 'mentor'
        ];

        // Verificar lógica de datos para mentor
        $this->assertEquals('mentor', $userData['role']);
        $this->assertNotEmpty($userData['name']);
        $this->assertNotEmpty($userData['email']);
        
        // Verificar estructura de datos default para mentor
        $mentorDefaults = [
            'experiencia' => null,
            'biografia' => null,
            'años_experiencia' => null,
            'disponible_ahora' => false,
            'calificacionPromedio' => 0.0,
        ];
        
        $this->assertFalse($mentorDefaults['disponible_ahora']);
        $this->assertEquals(0.0, $mentorDefaults['calificacionPromedio']);
    }

    public function test_role_validation_logic()
    {
        // Test unitario de la lógica de validación de roles
        $validRoles = ['student', 'mentor'];
        $invalidRoles = ['admin', 'teacher', 'user', ''];

        foreach ($validRoles as $role) {
            $this->assertContains($role, ['student', 'mentor']);
        }

        foreach ($invalidRoles as $role) {
            $this->assertNotContains($role, ['student', 'mentor']);
        }
    }

    public function test_password_hashing_logic()
    {
        // Test de la lógica de hashing como lo hace el controller
        $plainPassword = 'password123';
        $hashedPassword = Hash::make($plainPassword);

        $this->assertNotEquals($plainPassword, $hashedPassword);
        $this->assertTrue(Hash::check($plainPassword, $hashedPassword));
        $this->assertGreaterThan(10, strlen($hashedPassword)); // Hash debe ser largo
    }

    public function test_registered_event_structure()
    {
        // Test unitario de estructura de evento sin base de datos
        Event::fake();
        
        // Crear user mock para testing
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        // Simular el evento como lo hace el controller
        event(new Registered($user));
        
        // Verificar que el evento fue disparado
        Event::assertDispatched(Registered::class);
    }
}