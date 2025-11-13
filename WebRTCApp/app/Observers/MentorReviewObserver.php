<?php

namespace App\Observers;

use App\Models\MentorReview;

class MentorReviewObserver
{
    public function created(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
    }

    public function updated(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
    }

    public function deleted(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
    }
}
