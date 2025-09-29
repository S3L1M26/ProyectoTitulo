<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Aprendiz extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'aprendices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'semestre',
        'intereses',
        'objetivos',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'intereses' => 'array',
        'semestre' => 'integer',
    ];

    /**
     * Get the user that owns the student profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The areas of interest that belong to the student.
     */
    public function areasInteres()
    {
        return $this->belongsToMany(AreaInteres::class, 'aprendiz_area_interes');
    }

}
