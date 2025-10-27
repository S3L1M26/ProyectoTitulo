<?php

namespace Tests\Unit\Controllers\Auth;

use Tests\TestCase;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EmailVerificationNotificationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_controller_can_be_instantiated()
    {
        $controller = new EmailVerificationNotificationController();
        
        $this->assertInstanceOf(EmailVerificationNotificationController::class, $controller);
    }

    public function test_store_method_exists()
    {
        $controller = new EmailVerificationNotificationController();
        
        $this->assertTrue(method_exists($controller, 'store'));
    }

    public function test_store_method_returns_redirect_response()
    {
        $reflection = new \ReflectionMethod(
            EmailVerificationNotificationController::class,
            'store'
        );
        
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Http\RedirectResponse', $returnType->getName());
    }

    public function test_store_method_accepts_request_parameter()
    {
        $reflection = new \ReflectionMethod(
            EmailVerificationNotificationController::class,
            'store'
        );
        
        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('request', $param->getName());
        $this->assertEquals('Illuminate\Http\Request', $param->getType()->getName());
    }

    public function test_controller_is_in_correct_namespace()
    {
        $controller = new EmailVerificationNotificationController();
        
        $this->assertEquals(
            'App\Http\Controllers\Auth\EmailVerificationNotificationController',
            get_class($controller)
        );
    }

    public function test_controller_extends_base_controller()
    {
        $controller = new EmailVerificationNotificationController();
        
        $this->assertInstanceOf(
            'App\Http\Controllers\Controller',
            $controller
        );
    }

    public function test_store_method_has_proper_visibility()
    {
        $reflection = new \ReflectionMethod(
            EmailVerificationNotificationController::class,
            'store'
        );
        
        $this->assertTrue($reflection->isPublic());
        $this->assertFalse($reflection->isStatic());
    }

    public function test_controller_has_no_constructor_dependencies()
    {
        $reflection = new \ReflectionClass(EmailVerificationNotificationController::class);
        $constructor = $reflection->getConstructor();
        
        if ($constructor) {
            $this->assertCount(0, $constructor->getParameters());
        } else {
            // Si no tiene constructor, es válido también
            $this->assertTrue(true);
        }
    }

    public function test_controller_methods_count()
    {
        $reflection = new \ReflectionClass(EmailVerificationNotificationController::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filtrar solo métodos propios (no heredados)
        $ownMethods = array_filter($methods, function($method) {
            return $method->class === EmailVerificationNotificationController::class;
        });
        
        $this->assertGreaterThanOrEqual(1, count($ownMethods));
    }

    public function test_store_method_signature_is_correct()
    {
        $method = new \ReflectionMethod(
            EmailVerificationNotificationController::class,
            'store'
        );
        
        // Verificar que el método acepta Request y retorna RedirectResponse
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertStringContainsString('RedirectResponse', $returnType->getName());
    }
}
