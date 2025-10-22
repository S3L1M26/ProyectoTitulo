<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Aprendiz;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class AprendizTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // No external dependencies to fake for basic model tests
    }

    public function test_it_uses_correct_table_name()
    {
        $aprendiz = new Aprendiz();
        
        $this->assertEquals('aprendices', $aprendiz->getTable());
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $aprendiz = new Aprendiz();
        
        $expected = [
            'semestre',
            'objetivos',
            'user_id',
        ];
        
        $this->assertEquals($expected, $aprendiz->getFillable());
    }

    public function test_it_has_correct_casts()
    {
        $aprendiz = new Aprendiz();
        
        $casts = $aprendiz->getCasts();
        
        $this->assertEquals('integer', $casts['semestre']);
    }

    public function test_it_belongs_to_user()
    {
        $aprendiz = new Aprendiz();
        
        $relation = $aprendiz->user();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_it_can_set_semestre_as_integer()
    {
        $aprendiz = new Aprendiz();
        
        $aprendiz->semestre = '6';
        
        $this->assertIsInt($aprendiz->semestre);
        $this->assertEquals(6, $aprendiz->semestre);
    }

    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'semestre' => 5,
            'objetivos' => 'Learn advanced Laravel techniques',
            'user_id' => 1,
        ];

        $aprendiz = new Aprendiz($attributes);

        $this->assertEquals(5, $aprendiz->semestre);
        $this->assertEquals('Learn advanced Laravel techniques', $aprendiz->objetivos);
        $this->assertEquals(1, $aprendiz->user_id);
    }

    public function test_it_handles_null_semestre_gracefully()
    {
        $aprendiz = new Aprendiz(['semestre' => null]);
        
        $this->assertNull($aprendiz->semestre);
    }

    public function test_it_handles_empty_objetivos_gracefully()
    {
        $aprendiz = new Aprendiz(['objetivos' => '']);
        
        $this->assertEquals('', $aprendiz->objetivos);
    }
}