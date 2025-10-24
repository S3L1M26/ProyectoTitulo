<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\ProfileController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Response as InertiaResponse;
use Mockery;

class ProfileControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Simple setup without complex mocking
        $this->artisan('config:clear');
    }

    public function test_controller_extends_base_controller()
    {
        $controller = new ProfileController();
        
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $controller);
    }

    public function test_edit_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('edit'));
    }

    public function test_update_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('update'));
    }

    public function test_destroy_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('destroy'));
    }

    public function test_get_areas_interes_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('getAreasInteres'));
    }

    public function test_update_aprendiz_profile_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('updateAprendizProfile'));
    }

    public function test_update_mentor_profile_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('updateMentorProfile'));
    }

    public function test_toggle_mentor_disponibilidad_method_exists()
    {
        $controller = new ProfileController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('toggleMentorDisponibilidad'));
    }

    public function test_controller_has_seven_public_methods()
    {
        $reflection = new \ReflectionClass(ProfileController::class);
        $publicMethods = array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), function($method) {
            return $method->class === ProfileController::class;
        });
        
        $this->assertCount(7, $publicMethods);
    }

    public function test_edit_method_accepts_request_parameter()
    {
        $reflection = new \ReflectionMethod(ProfileController::class, 'edit');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
    }

    public function test_update_method_accepts_profile_update_request()
    {
        $reflection = new \ReflectionMethod(ProfileController::class, 'update');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}