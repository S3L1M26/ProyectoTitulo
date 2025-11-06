<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Mentoria extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'mentorias';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'solicitud_id',
        'aprendiz_id',
        'mentor_id',
        'fecha',
        'hora',
        'duracion_minutos',
        'enlace_reunion',
        'zoom_meeting_id',
        'zoom_password',
        'estado',
        'notas_mentor',
        'notas_aprendiz',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
        'duracion_minutos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to model's array form.
     */
    protected $appends = [
        'fecha_hora_completa',
        'fecha_formateada',
        'hora_formateada',
        'esta_en_curso',
        'ha_finalizado',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Relación con la solicitud de mentoría original.
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Models\SolicitudMentoria::class, 'solicitud_id');
    }

    /**
     * Relación con el aprendiz (estudiante).
     */
    public function aprendiz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprendiz_id');
    }

    /**
     * Relación con el mentor.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener fecha y hora como un solo objeto Carbon.
     */
    public function getFechaHoraCompletaAttribute(): Carbon
    {
        return Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora->format('H:i:s'));
    }

    /**
     * Obtener fecha formateada en español.
     */
    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
    }

    /**
     * Obtener hora formateada (HH:MM).
     */
    public function getHoraFormateadaAttribute(): string
    {
        return $this->hora->format('H:i');
    }

    /**
     * Verificar si la mentoría está en curso.
     */
    public function getEstaEnCursoAttribute(): bool
    {
        $ahora = now();
        $inicio = $this->fecha_hora_completa;
        $fin = $inicio->copy()->addMinutes($this->duracion_minutos);

        return $ahora->between($inicio, $fin) && $this->estado === 'confirmada';
    }

    /**
     * Verificar si la mentoría ya finalizó.
     */
    public function getHaFinalizadoAttribute(): bool
    {
        $fin = $this->fecha_hora_completa->addMinutes($this->duracion_minutos);
        return now()->greaterThan($fin);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrar mentorías confirmadas.
     */
    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    /**
     * Filtrar mentorías completadas.
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    /**
     * Filtrar mentorías canceladas.
     */
    public function scopeCanceladas($query)
    {
        return $query->where('estado', 'cancelada');
    }

    /**
     * Filtrar mentorías próximas (fecha >= hoy).
     */
    public function scopeProximas($query)
    {
        return $query->where('fecha', '>=', now()->toDateString())
                     ->orderBy('fecha')
                     ->orderBy('hora');
    }

    /**
     * Filtrar mentorías de un aprendiz específico.
     */
    public function scopeDeAprendiz($query, int $aprendizId)
    {
        return $query->where('aprendiz_id', $aprendizId);
    }

    /**
     * Filtrar mentorías de un mentor específico.
     */
    public function scopeDeMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    /**
     * Filtrar mentorías de hoy.
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', now()->toDateString());
    }

    /**
     * Filtrar mentorías de esta semana.
     */
    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('fecha', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Marcar la mentoría como completada.
     */
    public function completar(?string $notasMentor = null, ?string $notasAprendiz = null): bool
    {
        $this->estado = 'completada';
        
        if ($notasMentor) {
            $this->notas_mentor = $notasMentor;
        }
        
        if ($notasAprendiz) {
            $this->notas_aprendiz = $notasAprendiz;
        }

        return $this->save();
    }

    /**
     * Cancelar la mentoría.
     */
    public function cancelar(): bool
    {
        $this->estado = 'cancelada';
        return $this->save();
    }

    /**
     * Verificar si el usuario puede unirse a la mentoría.
     */
    public function puedeUnirse(User $user): bool
    {
        return ($user->id === $this->aprendiz_id || $user->id === $this->mentor_id) 
               && $this->estado === 'confirmada'
               && !empty($this->enlace_reunion);
    }
}
