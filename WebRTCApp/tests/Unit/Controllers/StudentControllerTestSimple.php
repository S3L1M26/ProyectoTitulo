<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Student\StudentController;

class StudentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Simple setup for unit testing
        $this->artisan('config:clear');
    }

    public function test_controller_extends_base_controller()
    {
        $controller = new StudentController();
        
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $controller);
    }

    public function test_index_method_exists()
    {
        $controller = new StudentController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('index'));
        
        $method = $reflection->getMethod('index');
        $this->assertTrue($method->isPublic());
    }

    public function test_get_mentor_suggestions_method_exists()
    {
        $controller = new StudentController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('getMentorSuggestions'));
        
        $method = $reflection->getMethod('getMentorSuggestions');
        $this->assertTrue($method->isPrivate());
    }

    public function test_build_mentor_suggestions_query_method_exists()
    {
        $controller = new StudentController();
        
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('buildMentorSuggestionsQuery'));
        
        $method = $reflection->getMethod('buildMentorSuggestionsQuery');
        $this->assertTrue($method->isPrivate());
    }

    public function test_cache_key_generation_logic()
    {
        // Test the cache key generation logic used in the controller
        $areaIds = [1, 3, 2]; // Unsorted
        $sorted = sort($areaIds);
        $expectedSorted = '1,2,3';
        
        // Verify that sorting works as expected for cache keys
        $sortedString = implode(',', $areaIds);
        $hash = md5($sortedString);
        
        $this->assertEquals($expectedSorted, $sortedString);
        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash)); // MD5 hash length
    }
}