<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'user_id',
        'rating',
        'comment',
        'addressed_interests',
        'interests_clarity',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Devolver representación anonimizada para props Inertia.
     */
    public function toAnonymousArray(): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'addressed_interests' => $this->addressed_interests,
            'interests_clarity' => $this->interests_clarity ? (int) $this->interests_clarity : null,
            'created_at' => $this->created_at,
        ];
    }
}
