<?php

namespace App\Models\Models;

use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudMentoria extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The factory that should be used to generate instances.
     *
     * @var string
     */
    protected static string $factory = \Database\Factories\SolicitudMentoriaFactory::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'estudiante_id',
        'mentor_id',
        'mensaje',
        'estado',
        'fecha_solicitud',
        'fecha_respuesta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_respuesta' => 'datetime',
    ];

    /**
     * Get the student user that made the request.
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    /**
     * Get the mentor user that received the request.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the aprendiz profile.
     */
    public function aprendiz(): BelongsTo
    {
        return $this->belongsTo(Aprendiz::class, 'estudiante_id', 'user_id');
    }

    /**
     * Get the mentor profile.
     */
    public function mentorProfile(): BelongsTo
    {
        return $this->belongsTo(Mentor::class, 'mentor_id', 'user_id');
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope a query to only include accepted requests.
     */
    public function scopeAceptadas($query)
    {
        return $query->where('estado', 'aceptada');
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'rechazada');
    }
}
