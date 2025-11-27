<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VocationalSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocationalSurveyICVTest extends TestCase
{
    use RefreshDatabase;

    public function test_icv_is_calculated_as_average_with_two_decimals(): void
    {
        $student = User::factory()->student()->create();

        $payload = [
            'clarity_interest' => 5,
            'confidence_area' => 3,
            'platform_usefulness' => 2,
            'mentorship_usefulness' => 1,
            'recent_change_reason' => 'Exploring options',
        ];

        $response = $this->actingAs($student)->post(route('student.vocational.store'), $payload);

        $response->assertRedirect(route('student.vocational'));

        $survey = VocationalSurvey::first();

        $this->assertNotNull($survey);
        $this->assertEquals($student->id, $survey->student_id);
        $this->assertEquals(2.75, $survey->icv);
        $this->assertSame($payload['clarity_interest'], $survey->clarity_interest);
        $this->assertSame($payload['mentorship_usefulness'], $survey->mentorship_usefulness);
    }
}
