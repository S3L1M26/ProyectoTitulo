<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VocationalSurvey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VocationalSurvey>
 */
class VocationalSurveyFactory extends Factory
{
    protected $model = VocationalSurvey::class;

    public function definition(): array
    {
        $clarity = $this->faker->numberBetween(1, 5);
        $confidence = $this->faker->numberBetween(1, 5);
        $platform = $this->faker->numberBetween(1, 5);
        $mentorship = $this->faker->numberBetween(1, 5);

        $icv = round(($clarity + $confidence + $platform + $mentorship) / 4, 2);

        return [
            'student_id' => User::factory()->student(),
            'clarity_interest' => $clarity,
            'confidence_area' => $confidence,
            'platform_usefulness' => $platform,
            'mentorship_usefulness' => $mentorship,
            'recent_change_reason' => $this->faker->optional()->sentence(),
            'icv' => $icv,
        ];
    }
}
