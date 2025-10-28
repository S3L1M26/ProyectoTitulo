<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function aprendiz()
    {
        return $this->hasOne(Aprendiz::class);
    }

    public function mentor()
    {
        return $this->hasOne(Mentor::class);
    }

    /**
     * Get the student's documents.
     */
    public function studentDocuments()
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Get the student's latest document.
     */
    public function latestStudentDocument()
    {
        return $this->hasOne(StudentDocument::class)->latestOfMany();
    }

    /**
     * Send a password reset notification to the user.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Get profile completeness with weighted fields
     */
    public function getProfileCompletenessAttribute(): array
    {
        if ($this->role === 'student') {
            return $this->calculateStudentCompleteness();
        } elseif ($this->role === 'mentor') {
            return $this->calculateMentorCompleteness();
        }
        
        return [
            'percentage' => 100, 
            'missing_fields' => [],
            'completed_fields' => [],
            'weights' => []
        ];
    }

    /**
     * Calculate student profile completeness with weighted fields
     */
    private function calculateStudentCompleteness(): array
    {
        // Pesos diferenciados basados en importancia para matching
        $weights = [
            'areas_interes' => 40,  // Más importante para emparejamiento
            'semestre' => 35,       // Importante para nivel académico
            'objetivos' => 25       // Importante pero menos crítico
        ];

        $completedFields = [];
        $missingFields = [];
        $totalScore = 0;

        // Cargar relación si es necesario
        if (!$this->relationLoaded('aprendiz')) {
            $this->load('aprendiz.areasInteres');
        }

        $aprendiz = $this->aprendiz;

        // Verificar semestre
        if ($aprendiz && $aprendiz->semestre && $aprendiz->semestre > 0) {
            $completedFields[] = 'semestre';
            $totalScore += $weights['semestre'];
        } else {
            $missingFields[] = 'Semestre';
        }

        // Verificar áreas de interés
        if ($aprendiz && $aprendiz->areasInteres && $aprendiz->areasInteres->count() > 0) {
            $completedFields[] = 'areas_interes';
            $totalScore += $weights['areas_interes'];
        } else {
            $missingFields[] = 'Áreas de interés';
        }

        // Verificar objetivos
        if ($aprendiz && $aprendiz->objetivos && trim($aprendiz->objetivos) !== '') {
            $completedFields[] = 'objetivos';
            $totalScore += $weights['objetivos'];
        } else {
            $missingFields[] = 'Objetivos personales';
        }

        return [
            'percentage' => $totalScore,
            'missing_fields' => $missingFields,
            'completed_fields' => $completedFields,
            'weights' => $weights
        ];
    }

    /**
     * Calculate mentor profile completeness with weighted fields
     */
    private function calculateMentorCompleteness(): array
    {
        // Pesos diferenciados basados en impacto para estudiantes
        $weights = [
            'experiencia' => 30,        // Muy importante para credibilidad
            'areas_interes' => 25,      // Crítico para matching
            'biografia' => 20,          // Importante para confianza
            'años_experiencia' => 15,   // Complementario
            'disponibilidad' => 10      // Menos crítico (puede ser "por coordinar")
        ];

        $completedFields = [];
        $missingFields = [];
        $totalScore = 0;

        // Cargar relación si es necesario
        if (!$this->relationLoaded('mentor')) {
            $this->load('mentor.areasInteres');
        }

        $mentor = $this->mentor;

        if (!$mentor) {
            return [
                'percentage' => 0,
                'missing_fields' => ['Experiencia profesional', 'Biografía', 'Años de experiencia', 'Disponibilidad', 'Áreas de especialidad'],
                'completed_fields' => [],
                'weights' => $weights
            ];
        }

        // Verificar experiencia (peso 30%)
        if ($mentor->experiencia && strlen(trim($mentor->experiencia)) >= 50) {
            $completedFields[] = 'experiencia';
            $totalScore += $weights['experiencia'];
        } else {
            $missingFields[] = 'Experiencia profesional detallada';
        }

        // Verificar áreas de especialidad (peso 25%)
        if ($mentor->areasInteres && $mentor->areasInteres->count() > 0) {
            $completedFields[] = 'areas_interes';
            $totalScore += $weights['areas_interes'];
        } else {
            $missingFields[] = 'Áreas de especialidad';
        }

        // Verificar biografía (peso 20%)
        if ($mentor->biografia && strlen(trim($mentor->biografia)) >= 100) {
            $completedFields[] = 'biografia';
            $totalScore += $weights['biografia'];
        } else {
            $missingFields[] = 'Biografía personal';
        }

        // Verificar años de experiencia (peso 15%)
        if ($mentor->años_experiencia && $mentor->años_experiencia > 0) {
            $completedFields[] = 'años_experiencia';
            $totalScore += $weights['años_experiencia'];
        } else {
            $missingFields[] = 'Años de experiencia';
        }

        // Verificar disponibilidad (peso 10%)
        if ($mentor->disponibilidad && strlen(trim($mentor->disponibilidad)) > 0) {
            $completedFields[] = 'disponibilidad';
            $totalScore += $weights['disponibilidad'];
        } else {
            $missingFields[] = 'Disponibilidad';
        }

        return [
            'percentage' => $totalScore,
            'missing_fields' => $missingFields,
            'completed_fields' => $completedFields,
            'weights' => $weights
        ];
    }
}
