<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AreaInteres;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class AreaInteresTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // No external dependencies to fake for basic model tests
    }

    public function test_it_uses_correct_table_name()
    {
        $areaInteres = new AreaInteres();
        
        $this->assertEquals('areas_interes', $areaInteres->getTable());
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $areaInteres = new AreaInteres();
        
        $expected = [
            'nombre',
            'descripcion',
        ];
        
        $this->assertEquals($expected, $areaInteres->getFillable());
    }

    public function test_it_belongs_to_many_aprendices()
    {
        $areaInteres = new AreaInteres();
        
        $relation = $areaInteres->aprendices();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
        $this->assertEquals('aprendiz_area_interes', $relation->getTable());
    }

    public function test_it_belongs_to_many_mentores()
    {
        $areaInteres = new AreaInteres();
        
        $relation = $areaInteres->mentores();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
        $this->assertEquals('mentor_area_interes', $relation->getTable());
    }

    public function test_it_can_be_instantiated_with_attributes()
    {
        $attributes = [
            'nombre' => 'Desarrollo Web',
            'descripcion' => 'Área enfocada en el desarrollo de aplicaciones web',
        ];

        $areaInteres = new AreaInteres($attributes);

        $this->assertEquals('Desarrollo Web', $areaInteres->nombre);
        $this->assertEquals('Área enfocada en el desarrollo de aplicaciones web', $areaInteres->descripcion);
    }

    public function test_it_handles_empty_descripcion_gracefully()
    {
        $areaInteres = new AreaInteres([
            'nombre' => 'Machine Learning',
            'descripcion' => '',
        ]);
        
        $this->assertEquals('Machine Learning', $areaInteres->nombre);
        $this->assertEquals('', $areaInteres->descripcion);
    }

    public function test_it_has_factory_trait()
    {
        $areaInteres = new AreaInteres();
        
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($areaInteres)));
    }
}