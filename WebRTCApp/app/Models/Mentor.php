<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'experiencia',
        'biografia',
        'años_experiencia',
        'disponibilidad',
        'disponibilidad_detalle',
        'disponible_ahora',
        'calificacionPromedio',
        'cv_verified',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calificacionPromedio' => 'float',
        'disponible_ahora' => 'boolean',
        'cv_verified' => 'boolean',
        'años_experiencia' => 'integer',
    ];

    /**
     * Get the user that owns the mentor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The areas of interest that belong to the mentor.
     */
    public function areasInteres()
    {
        return $this->belongsToMany(AreaInteres::class, 'mentor_area_interes');
    }

    /**
     * Get the mentor's documents (CVs).
     */
    public function documents()
    {
        return $this->hasMany(MentorDocument::class, 'user_id', 'user_id');
    }

    /**
     * Get the mentor's latest document.
     */
    public function latestDocument()
    {
        return $this->hasOne(MentorDocument::class, 'user_id', 'user_id')->latestOfMany();
    }

    /**
     * Get the rating in stars format.
     */
    public function getStarsRatingAttribute(): string
    {
        $rating = $this->calificacionPromedio ?? 0;
        return number_format($rating, 1) . ' ⭐';
    }

    /**
     * Get the rating as a percentage for progress bars.
     */
    public function getRatingPercentageAttribute(): int
    {
        $rating = $this->calificacionPromedio ?? 0;
        return (int) (($rating / 5) * 100);
    }

    /**
     * Get the mentorships for the mentor.
     */
    // public function mentorias()
    // {
    //     return $this->hasMany(Mentoria::class, 'idMentor');
    // }
}
