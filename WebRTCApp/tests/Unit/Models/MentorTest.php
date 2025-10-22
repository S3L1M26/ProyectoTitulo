<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Mentor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class MentorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // No external dependencies to fake for basic model tests
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $mentor = new Mentor();
        
        $expected = [
            'experiencia',
            'biografia',
            'años_experiencia',
            'disponibilidad',
            'disponibilidad_detalle',
            'disponible_ahora',
            'calificacionPromedio',
            'user_id',
        ];
        
        $this->assertEquals($expected, $mentor->getFillable());
    }

    public function test_it_has_correct_casts()
    {
        $mentor = new Mentor();
        
        $casts = $mentor->getCasts();
        
        $this->assertEquals('float', $casts['calificacionPromedio']);
        $this->assertEquals('boolean', $casts['disponible_ahora']);
        $this->assertEquals('integer', $casts['años_experiencia']);
    }

    public function test_it_belongs_to_user()
    {
        $mentor = new Mentor();
        
        $relation = $mentor->user();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_it_can_set_calificacion_promedio_as_float()
    {
        $mentor = new Mentor();
        
        $mentor->calificacionPromedio = '4.5';
        
        $this->assertIsFloat($mentor->calificacionPromedio);
        $this->assertEquals(4.5, $mentor->calificacionPromedio);
    }

    public function test_it_can_set_disponible_ahora_as_boolean()
    {
        $mentor = new Mentor();
        
        $mentor->disponible_ahora = '1';
        
        $this->assertIsBool($mentor->disponible_ahora);
        $this->assertTrue($mentor->disponible_ahora);
    }

    public function test_it_can_set_años_experiencia_as_integer()
    {
        $mentor = new Mentor();
        
        $mentor->años_experiencia = '5';
        
        $this->assertIsInt($mentor->años_experiencia);
        $this->assertEquals(5, $mentor->años_experiencia);
    }

    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'experiencia' => 'Senior Developer',
            'biografia' => 'Experienced in Laravel',
            'años_experiencia' => 8,
            'disponibilidad' => 'weekends',
            'disponibilidad_detalle' => 'Available on Saturday mornings',
            'disponible_ahora' => true,
            'calificacionPromedio' => 4.8,
            'user_id' => 1,
        ];

        $mentor = new Mentor($attributes);

        $this->assertEquals('Senior Developer', $mentor->experiencia);
        $this->assertEquals('Experienced in Laravel', $mentor->biografia);
        $this->assertEquals(8, $mentor->años_experiencia);
        $this->assertEquals('weekends', $mentor->disponibilidad);
        $this->assertEquals('Available on Saturday mornings', $mentor->disponibilidad_detalle);
        $this->assertTrue($mentor->disponible_ahora);
        $this->assertEquals(4.8, $mentor->calificacionPromedio);
        $this->assertEquals(1, $mentor->user_id);
    }
}