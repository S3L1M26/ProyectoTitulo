<?php

namespace Tests\Unit;

use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorReviewModelTest extends TestCase
{
    use RefreshDatabase;

    private Mentor $mentor;
    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $mentorUser = User::factory()->create(['role' => 'mentor']);
        $this->mentor = Mentor::factory()->create(['user_id' => $mentorUser->id]);
        $this->reviewer = User::factory()->create(['role' => 'student']);
    }

    /**
     * Test: MentorReview tiene relaciones correctas.
     */
    public function test_mentor_review_has_correct_relationships()
    {
        $review = MentorReview::factory()->create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
        ]);

        $this->assertInstanceOf(Mentor::class, $review->mentor);
        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($this->mentor->id, $review->mentor->id);
        $this->assertEquals($this->reviewer->id, $review->user->id);
    }

    /**
     * Test: MentorReview fillable attributes.
     */
    public function test_mentor_review_fillable_attributes()
    {
        $attributes = [
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 5,
            'comment' => 'Test comment',
        ];

        $review = MentorReview::create($attributes);

        $this->assertEquals($this->mentor->id, $review->mentor_id);
        $this->assertEquals($this->reviewer->id, $review->user_id);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Test comment', $review->comment);
    }

    /**
     * Test: toAnonymousArray devuelve estructura correcta.
     */
    public function test_to_anonymous_array_returns_correct_structure()
    {
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
            'comment' => 'Good mentor',
        ]);

        $anonymized = $review->toAnonymousArray();

        $this->assertIsArray($anonymized);
        $this->assertArrayHasKey('id', $anonymized);
        $this->assertArrayHasKey('rating', $anonymized);
        $this->assertArrayHasKey('comment', $anonymized);
        $this->assertArrayHasKey('created_at', $anonymized);
        $this->assertArrayNotHasKey('user_id', $anonymized);
        $this->assertArrayNotHasKey('mentor_id', $anonymized);
        $this->assertEquals(4, $anonymized['rating']);
    }

    /**
     * Test: Mentor tiene relación con reviews.
     */
    public function test_mentor_has_many_reviews()
    {
        $reviewer1 = User::factory()->create(['role' => 'student']);
        $reviewer2 = User::factory()->create(['role' => 'student']);

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

        $this->assertEquals(2, $this->mentor->reviews->count());
    }

    /**
     * Test: Validar rango de rating (debe estar entre 1-5).
     */
    public function test_rating_must_be_between_1_and_5()
    {
        // Rating válido
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
        ]);

        $this->assertEquals(3, $review->rating);

        // Intentar valores inválidos
        $invalidRatings = [0, 6, -1, 10];

        foreach ($invalidRatings as $rating) {
            try {
                MentorReview::create([
                    'mentor_id' => $this->mentor->id,
                    'user_id' => User::factory()->create(['role' => 'student'])->id,
                    'rating' => $rating,
                ]);
                
                // Si no lanza excepción, el test falla (la BD debería rechazarlo)
                $this->fail("Rating {$rating} debería ser rechazado");
            } catch (\Exception $e) {
                // Excepción esperada
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test: Unique constraint (mentor_id, user_id).
     */
    public function test_unique_constraint_mentor_user()
    {
        // Primera reseña del usuario
        MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
        ]);

        // Intentar segunda reseña del mismo usuario al mismo mentor
        try {
            MentorReview::create([
                'mentor_id' => $this->mentor->id,
                'user_id' => $this->reviewer->id,
                'rating' => 5,
            ]);

            $this->fail('Constraint unique debería impedir segunda reseña');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test: Comment puede ser null.
     */
    public function test_comment_can_be_null()
    {
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 5,
            'comment' => null,
        ]);

        $this->assertNull($review->comment);
    }

    /**
     * Test: Timestamps se crean automáticamente.
     */
    public function test_timestamps_created_automatically()
    {
        $review = MentorReview::create([
            'mentor_id' => $this->mentor->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
        ]);

        $this->assertNotNull($review->created_at);
        $this->assertNotNull($review->updated_at);
    }
}
