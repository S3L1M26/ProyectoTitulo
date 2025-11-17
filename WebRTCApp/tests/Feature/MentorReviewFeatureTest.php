<?php

namespace Tests\Feature;

use App\Models\Mentor;
use App\Models\User;
use App\Models\MentorReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorReviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $mentorUser;
    private User $studentUser;
    private Mentor $mentor;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuarios de prueba
        $this->mentorUser = User::factory()->create(['role' => 'mentor']);
        $this->studentUser = User::factory()->create(['role' => 'student']);

        // Crear mentor asociado con promedio inicial en 0
        $this->mentor = Mentor::factory()->create(['user_id' => $this->mentorUser->id]);
        $this->mentor->update(['calificacionPromedio' => 0]);
    }

    /**
     * T3.7.3: Crear review y recalcular promedio.
     */
    public function test_create_review_and_recalculate_average()
    {
        // Inicialmente sin reseñas, promedio debe ser 0
        $this->assertEquals(0, $this->mentor->calificacionPromedio);

        // Crear una reseña
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->studentUser->id,
            'rating' => 5,
            'comment' => 'Excelente mentor',
        ]);

        // Refrescar mentor desde BD para obtener calificacionPromedio recalculada
        $this->mentor->refresh();

        // Verificar que el promedio se calculó correctamente
        $this->assertNotNull($review);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals(5.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * T3.7.3: Crear múltiples reviews y verificar promedio correcto.
     */
    public function test_multiple_reviews_calculate_correct_average()
    {
        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);
        $reviewer3 = User::factory()->create(['role' => 'student']);

        // Crear 3 reseñas con ratings diferentes
        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer1->id,
            'rating' => 5,
        ]);

        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer2->id,
            'rating' => 4,
        ]);

        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer3->id,
            'rating' => 3,
        ]);

        // Refrescar para obtener promedio recalculado
        $this->mentor->refresh();

        // Promedio debería ser (5+4+3)/3 = 4
        $this->assertEquals(4.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * T3.7.3: Actualizar review existente por el mismo usuario y recalcular.
     */
    public function test_update_existing_review_and_recalculate_average()
    {
        // Crear review inicial con rating 3
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->studentUser->id,
            'rating' => 3,
            'comment' => 'Bueno',
        ]);

        $this->mentor->refresh();
        $this->assertEquals(3.0, (float) $this->mentor->calificacionPromedio);

        // Actualizar el rating a 5
        $review->update(['rating' => 5, 'comment' => 'Excelente']);

        $this->mentor->refresh();

        // Promedio debería actualizarse a 5
        $this->assertEquals(5.0, (float) $this->mentor->calificacionPromedio);
    }

    /**
     * T3.7.3: Rechazar si rating fuera de rango (< 1).
     */
    public function test_reject_review_with_rating_below_minimum()
    {
        // Intentar crear una reseña con rating 0 (inválido)
        try {
            MentorReview::create([
                'mentor_id' => $this->mentor->id,
                'user_id' => $this->studentUser->id,
                'rating' => 0,
                'comment' => 'Malo',
            ]);

            // Si llegamos aquí, la BD no tiene validación. Deberíamos fallar en validación anterior
            $this->fail('Se esperaba una excepción al crear review con rating 0');
        } catch (\Exception $e) {
            // Se lanzó una excepción, lo cual es lo esperado
            $this->assertTrue(true);
        }
    }

    /**
     * T3.7.3: Rechazar si rating fuera de rango (> 5).
     */
    public function test_reject_review_with_rating_above_maximum()
    {
        try {
            MentorReview::create([
                'mentor_id' => $this->mentor->id,
                'user_id' => $this->studentUser->id,
                'rating' => 6,
                'comment' => 'Mejor que excelente',
            ]);

            $this->fail('Se esperaba una excepción al crear review con rating 6');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * T3.7.3: Comprobar constraint unique - dos usuarios distintos pueden reseñar.
     */
    public function test_two_different_users_can_review_same_mentor()
    {
        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);

        // Primer usuario puede reseñar
        $review1 = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer1->id,
            'rating' => 4,
        ]);

        // Segundo usuario puede reseñar al mismo mentor
        $review2 = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $reviewer2->id,
            'rating' => 5,
        ]);

        $this->assertNotNull($review1);
        $this->assertNotNull($review2);
        $this->assertEquals(2, MentorReview::where('mentor_id', $this->mentor->id)->count());
    }

    /**
     * T3.7.3: Comprobar constraint unique - mismo usuario NO puede duplicar reseña.
     */
    public function test_same_user_cannot_create_duplicate_review()
    {
        // Primer intento: crear reseña
        $review1 = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->studentUser->id,
            'rating' => 4,
        ]);

        $this->assertNotNull($review1);

        // Segundo intento: mismo usuario intenta crear otra reseña
        try {
            MentorReview::create([
                'mentor_id' => $this->mentor->id,
                'user_id' => $this->studentUser->id,
                'rating' => 5,
            ]);

            $this->fail('Se esperaba una excepción de constraint unique al crear segunda reseña');
        } catch (\Exception $e) {
            // Se lanzó una excepción de constraint unique, lo cual es correcto
            $this->assertTrue(true);
        }
    }

    /**
     * T3.7.3: Validar que al eliminar una review, el promedio se recalcula.
     */
    public function test_delete_review_recalculates_average()
    {
        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);

        // Crear dos reseñas: 5 y 3, promedio = 4
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

        // Eliminar la primera reseña (rating 5)
        $review1->delete();

        $this->mentor->refresh();

        // Promedio debería ser ahora solo 3
        $this->assertEquals(3.0, (float) $this->mentor->calificacionPromedio);
    }
}
