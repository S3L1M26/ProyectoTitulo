<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mentor;
use App\Models\AreaInteres;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MentorRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mentor_belongs_to_user()
    {
        $user = User::factory()->mentor()->create(['name' => 'John Mentor']);
        $mentor = Mentor::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $mentor->user);
        $this->assertEquals('John Mentor', $mentor->user->name);
        $this->assertEquals('mentor', $mentor->user->role);
    }

    #[Test]
    public function mentor_can_have_multiple_areas_of_interest()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();

        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);
        $react = AreaInteres::factory()->create(['nombre' => 'React']);

        $mentorProfile->areasInteres()->attach([$php->id, $laravel->id, $react->id]);

        $this->assertCount(3, $mentorProfile->areasInteres);
        $this->assertTrue($mentorProfile->areasInteres->contains('nombre', 'PHP'));
        $this->assertTrue($mentorProfile->areasInteres->contains('nombre', 'Laravel'));
        $this->assertTrue($mentorProfile->areasInteres->contains('nombre', 'React'));
    }

    #[Test]
    public function mentor_can_detach_areas_of_interest()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();

        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);
        $laravel = AreaInteres::factory()->create(['nombre' => 'Laravel']);

        $mentorProfile->areasInteres()->attach([$php->id, $laravel->id]);
        $this->assertCount(2, $mentorProfile->fresh()->areasInteres);

        $mentorProfile->areasInteres()->detach($php->id);
        $this->assertCount(1, $mentorProfile->fresh()->areasInteres);
        $this->assertFalse($mentorProfile->fresh()->areasInteres->contains('nombre', 'PHP'));
        $this->assertTrue($mentorProfile->fresh()->areasInteres->contains('nombre', 'Laravel'));
    }

    #[Test]
    public function stars_rating_attribute_returns_formatted_rating()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'calificacionPromedio' => 4.75
        ]);

        $this->assertEquals('4.8 ⭐', $mentorProfile->stars_rating);

        // Test con rating cero
        $mentorProfile2 = Mentor::factory()->for(User::factory()->mentor())->create([
            'calificacionPromedio' => 0.0
        ]);
        $this->assertEquals('0.0 ⭐', $mentorProfile2->stars_rating);
    }

    #[Test]
    public function rating_percentage_attribute_converts_rating_to_percentage()
    {
        $mentor = User::factory()->mentor()->create();
        
        // Test con rating 4.5 (90%)
        $mentorProfile1 = Mentor::factory()->for($mentor)->create([
            'calificacionPromedio' => 4.5
        ]);
        $this->assertEquals(90, $mentorProfile1->rating_percentage);

        // Test con rating 5.0 (100%)
        $mentorProfile2 = Mentor::factory()->for(User::factory()->mentor())->create([
            'calificacionPromedio' => 5.0
        ]);
        $this->assertEquals(100, $mentorProfile2->rating_percentage);

        // Test con rating 3.0 (60%)
        $mentorProfile3 = Mentor::factory()->for(User::factory()->mentor())->create([
            'calificacionPromedio' => 3.0
        ]);
        $this->assertEquals(60, $mentorProfile3->rating_percentage);

        // Test con rating nulo (0%)
        $mentorProfile4 = Mentor::factory()->for(User::factory()->mentor())->create([
            'calificacionPromedio' => 0.0
        ]);
        $this->assertEquals(0, $mentorProfile4->rating_percentage);
    }

    #[Test]
    public function mentor_with_no_areas_has_empty_collection()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create();

        $this->assertCount(0, $mentorProfile->areasInteres);
        $this->assertTrue($mentorProfile->areasInteres->isEmpty());
    }

    #[Test]
    public function multiple_mentors_can_share_same_area_of_interest()
    {
        $php = AreaInteres::factory()->create(['nombre' => 'PHP']);

        $mentor1 = User::factory()->mentor()->create(['name' => 'Mentor 1']);
        $mentorProfile1 = Mentor::factory()->for($mentor1)->create();
        $mentorProfile1->areasInteres()->attach($php->id);

        $mentor2 = User::factory()->mentor()->create(['name' => 'Mentor 2']);
        $mentorProfile2 = Mentor::factory()->for($mentor2)->create();
        $mentorProfile2->areasInteres()->attach($php->id);

        $mentor3 = User::factory()->mentor()->create(['name' => 'Mentor 3']);
        $mentorProfile3 = Mentor::factory()->for($mentor3)->create();
        $mentorProfile3->areasInteres()->attach($php->id);

        // Verificar que PHP tiene 3 mentores asociados
        $phpArea = AreaInteres::with('mentores')->find($php->id);
        $this->assertCount(3, $phpArea->mentores);
    }

    #[Test]
    public function mentor_attributes_are_properly_cast()
    {
        $mentor = User::factory()->mentor()->create();
        $mentorProfile = Mentor::factory()->for($mentor)->create([
            'calificacionPromedio' => '4.5',
            'disponible_ahora' => '1',
            'años_experiencia' => '10'
        ]);

        // Verificar tipos de datos
        $this->assertIsFloat($mentorProfile->calificacionPromedio);
        $this->assertIsBool($mentorProfile->disponible_ahora);
        $this->assertIsInt($mentorProfile->años_experiencia);

        // Verificar valores
        $this->assertEquals(4.5, $mentorProfile->calificacionPromedio);
        $this->assertTrue($mentorProfile->disponible_ahora);
        $this->assertEquals(10, $mentorProfile->años_experiencia);
    }
}
