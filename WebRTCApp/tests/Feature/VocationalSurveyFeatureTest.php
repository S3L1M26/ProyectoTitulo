<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VocationalSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VocationalSurveyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_vocational_survey_validation_rules_are_enforced(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post(route('student.vocational.store'), [
            'clarity_interest' => 0, // below min
            'confidence_area' => 6, // above max
            'platform_usefulness' => 'invalid', // not integer
            'mentorship_usefulness' => null, // required
            'recent_change_reason' => str_repeat('A', 205), // exceeds max
        ]);

        $response->assertSessionHasErrors([
            'clarity_interest',
            'confidence_area',
            'platform_usefulness',
            'mentorship_usefulness',
            'recent_change_reason',
        ]);
    }

    public function test_student_can_persist_and_retrieve_vocational_survey_snapshots(): void
    {
        $student = User::factory()->student()->create();

        Carbon::setTestNow(now());
        $firstPayload = [
            'clarity_interest' => 5,
            'confidence_area' => 4,
            'platform_usefulness' => 4,
            'mentorship_usefulness' => 5,
            'recent_change_reason' => 'First snapshot',
        ];
        $this->actingAs($student)
            ->post(route('student.vocational.store'), $firstPayload)
            ->assertRedirect(route('student.vocational'));

        Carbon::setTestNow(now()->addMinutes(5));
        $secondPayload = [
            'clarity_interest' => 3,
            'confidence_area' => 3,
            'platform_usefulness' => 4,
            'mentorship_usefulness' => 2,
            'recent_change_reason' => 'Second snapshot',
        ];
        $this->actingAs($student)
            ->post(route('student.vocational.store'), $secondPayload)
            ->assertRedirect(route('student.vocational'));

        Carbon::setTestNow(); // Reset test clock

        $otherStudent = User::factory()->student()->create();
        VocationalSurvey::factory()->for($otherStudent, 'student')->create();

        $listResponse = $this->actingAs($student)
            ->getJson(route('api.student.vocational-surveys.index'));

        $listResponse->assertOk()->assertJsonCount(2, 'data');

        $data = $listResponse->json('data');
        $this->assertEquals(3.0, $data[0]['icv']); // Latest snapshot first
        $this->assertEquals('Second snapshot', $data[0]['recent_change_reason']);
        $this->assertEquals(4.5, $data[1]['icv']); // Older snapshot second
        $this->assertEquals($student->id, $data[0]['student_id']);
        $this->assertEquals($student->id, $data[1]['student_id']);

        $latestResponse = $this->actingAs($student)
            ->getJson(route('api.student.vocational-surveys.latest'));

        $latestResponse->assertOk();
        $this->assertEquals($data[0]['id'], $latestResponse->json('data.id'));
        $this->assertEquals(3.0, $latestResponse->json('data.icv'));
        $this->assertEquals('Second snapshot', $latestResponse->json('data.recent_change_reason'));
    }
}
