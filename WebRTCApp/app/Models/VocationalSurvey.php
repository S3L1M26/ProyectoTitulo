<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocationalSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'clarity_interest',
        'confidence_area',
        'platform_usefulness',
        'mentorship_usefulness',
        'recent_change_reason',
        'icv',
    ];

    protected $casts = [
        'clarity_interest' => 'integer',
        'confidence_area' => 'integer',
        'platform_usefulness' => 'integer',
        'mentorship_usefulness' => 'integer',
        'icv' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
