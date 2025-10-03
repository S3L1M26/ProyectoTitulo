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
        'calificacionPromedio',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calificacionPromedio' => 'float',
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
     * Get the mentorships for the mentor.
     */
    // public function mentorias()
    // {
    //     return $this->hasMany(Mentoria::class, 'idMentor');
    // }
}
