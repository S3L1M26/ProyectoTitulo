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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}