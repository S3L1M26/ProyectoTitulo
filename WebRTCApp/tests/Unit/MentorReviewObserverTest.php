<?php

namespace Tests\Unit;

use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorReviewObserverTest extends TestCase
{
    use RefreshDatabase;

    private Mentor $mentor;

    protected function setUp(): void
    {
        parent::setUp();

        $mentorUser = User::factory()->create(['role' => 'mentor']);
        $this->mentor = Mentor::factory()->create(['user_id' => $mentorUser->id]);
        $this->mentor->update(['calificacionPromedio' => 0]);
    }

    /**
     * Test: Observer crea/actualiza el promedio al crear review.
     */
    public function test_observer_updates_average_on_create()
    {
        $reviewer = User::factory()->create(['role' => 'student']);

        $this->assertEquals(0, $this->mentor->calificacionPromedio);

        // Crear review
        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
        ]);

        // Refrescar mentor para obtener el promedio recalculado
        $this->mentor->refresh();

        // Promedio debería ser 5
        $this->assertEquals(5.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * Test: Observer actualiza promedio al modificar rating.
     */
    public function test_observer_updates_average_on_update()
    {
        $reviewer = User::factory()->create(['role' => 'student']);

        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer->id,
            'rating' => 2,
        ]);

        $this->mentor->refresh();
        $this->assertEquals(2.0, (float) $this->mentor->calificacionPromedio);

        // Actualizar rating a 4
        $review->update(['rating' => 4]);

        $this->mentor->refresh();

        // Promedio debería actualizarse a 4
        $this->assertEquals(4.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * Test: Observer recalcula promedio al eliminar review.
     */
    public function test_observer_updates_average_on_delete()
    {
        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);

        // Crear dos reviews: 5 y 3
        $review1 = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer1->id,
            'rating' => 5,
        ]);

        $review2 = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer2->id,
            'rating' => 3,
        ]);

        $this->mentor->refresh();
        $this->assertEquals(4.0, (float) $this->mentor->calificacionPromedio);

        // Eliminar review con rating 5
        $review1->delete();

        $this->mentor->refresh();

        // Promedio debería ser solo 3
        $this->assertEquals(3.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * Test: Promedio se calcula correctamente con múltiples reviews.
     */
    public function test_observer_calculates_correct_average_with_multiple_reviews()
    {
        $reviewers = User::factory(5)->create(['role' => 'student']);
        $ratings = [5, 4, 3, 5, 2]; // Promedio: (5+4+3+5+2)/5 = 3.8

        foreach ($reviewers as $index => $reviewer) {
            MentorReview::create([
                'mentor_id' => $this->mentor->id,
                'user_id' => $reviewer->id,
                'rating' => $ratings[$index],
            ]);
        }

        $this->mentor->refresh();

        // Promedio esperado: 3.8
        $this->assertEquals(3.8, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * Test: Promedio se resetea a 0 cuando no hay reviews.
     */
    public function test_average_resets_to_zero_when_all_reviews_deleted()
    {
        $reviewer = User::factory()->create(['role' => 'student']);

        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
        ]);

        $this->mentor->refresh();
        $this->assertEquals(5.0, (float) $this->mentor->calificacionPromedio);

        // Eliminar la única review
        $review->delete();

        $this->mentor->refresh();

        // Promedio debería volver a 0
        $this->assertEquals(0, $this->mentor->calificacionPromedio);
    }

    /**
     * Test: Observer no afecta otros mentores.
     */
    public function test_observer_only_affects_current_mentor()
    {
        $mentor2User = User::factory()->create(['role' => 'mentor']);
        $mentor2 = Mentor::factory()->create(['user_id' => $mentor2User->id]);

        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);

        // Crear review para mentor1
        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer1->id,
            'rating' => 5,
        ]);

        // Crear review para mentor2
        MentorReview::create([
            'mentor_id' => $mentor2->id,
            'user_id' => $reviewer2->id,
            'rating' => 3,
        ]);

        $this->mentor->refresh();
        $mentor2->refresh();

        // Verificar que cada mentor tiene su propio promedio
        $this->assertEquals(5.0, (float) $this->mentor->calificacionPromedio);
        $this->assertEquals(3.0, (float) $mentor2->calificacionPromedio);
    }
}
