<?php

namespace Tests\Feature\Controllers\Admin;

use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\User;
use App\Models\VocationalSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dashboard_shows_review_and_survey_stats(): void
    {
        $admin = $this->createAdmin();

        $mentorUser = User::factory()->create(['role' => 'mentor']);
        $mentor = Mentor::factory()->create([
            'user_id' => $mentorUser->id,
            'calificacionPromedio' => 0,
        ]);

        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);

        MentorReview::factory()->create([
            'mentor_id' => $mentor->id,
            'user_id' => $studentA->id,
            'rating' => 5,
            'comment' => 'Excelente',
        ]);

        MentorReview::factory()->create([
            'mentor_id' => $mentor->id,
            'user_id' => $studentB->id,
            'rating' => 3,
            'comment' => 'Buena sesión',
        ]);

        // Ajustar promedio almacenado para ranking (ordenado por reviews_count y promedio)
        $mentor->update(['calificacionPromedio' => 4.0]);

        VocationalSurvey::create([
            'student_id' => $studentA->id,
            'clarity_interest' => 4,
            'confidence_area' => 4,
            'platform_usefulness' => 4,
            'mentorship_usefulness' => 4,
            'recent_change_reason' => null,
            'icv' => 4.0,
        ]);

        VocationalSurvey::create([
            'student_id' => $studentB->id,
            'clarity_interest' => 5,
            'confidence_area' => 3,
            'platform_usefulness' => 4,
            'mentorship_usefulness' => 2,
            'recent_change_reason' => 'Buscando otra área',
            'icv' => 3.5,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertInertia(function (Assert $page) use ($mentorUser) {
                $page->component('Admin/Dashboard/Index')
                    ->has('reviewStats', function (Assert $stats) use ($mentorUser) {
                        $stats
                            ->where('total_reviews', 2)
                            ->where('average_rating', fn ($value) => is_numeric($value) && (float) $value === 4.0)
                            ->has('rating_distribution', 2)
                            ->where('rating_distribution.0.rating', 5)
                            ->where('rating_distribution.0.total', 1)
                            ->has('top_mentors', fn (Assert $topMentors) => $topMentors
                                ->has('0', fn (Assert $top) => $top
                                    ->where('name', $mentorUser->name)
                                    ->where('reviews_count', 2)
                                    ->etc()
                                )
                            )
                            ->has('recent_reviews', 2);
                    })
                    ->has('surveyStats', function (Assert $stats) {
                        $stats
                            ->where('total_surveys', 2)
                            ->where('average_icv', 3.75)
                            ->has('question_averages.clarity_interest')
                            ->has('question_averages.confidence_area')
                            ->has('question_averages.platform_usefulness')
                            ->has('question_averages.mentorship_usefulness')
                            ->has('latest_entries', 2);
                    });
            });
    }

    public function test_admin_users_index_lists_is_active_flag(): void
    {
        $admin = $this->createAdmin();
        $activeUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $inactiveUser = User::factory()->create(['role' => 'mentor', 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('users.data', function ($users) use ($activeUser, $inactiveUser) {
                    $collection = collect($users);
                    $hasActive = $collection->contains(fn ($u) => $u['email'] === $activeUser->email && $u['is_active'] === true);
                    $hasInactive = $collection->contains(fn ($u) => $u['email'] === $inactiveUser->email && $u['is_active'] === false);
                    return $hasActive && $hasInactive;
                })
            );
    }

    public function test_admin_can_update_user_status_and_email(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create([
            'role' => 'student',
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Nuevo Nombre',
            'email' => 'nuevo@example.com',
            'is_active' => false,
        ];

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $payload)
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nuevo Nombre',
            'email' => 'nuevo@example.com',
            'is_active' => 0,
        ]);
    }

    public function test_non_admin_is_redirected_from_admin_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }
}
