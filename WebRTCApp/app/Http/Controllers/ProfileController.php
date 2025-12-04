<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Aprendiz;
use App\Models\AreaInteres;
use App\Models\Mentor;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // Refrescar el usuario desde la base de datos para obtener los datos más actualizados
        $user = Auth::user()->fresh();

        // Cargar datos del perfil según el rol
        if ($user->role === 'student') {
            $user->load(['aprendiz.areasInteres', 'latestStudentDocument']);
        } elseif ($user->role === 'mentor') {
            // Refrescar también el mentor para asegurar datos frescos de disponibilidad
            $user->mentor?->refresh();
            $user->load([
                'mentor.areasInteres',
                'mentor.reviews' => function($query) {
                    // Cargar las 5 reseñas más recientes anónimas
                    $query->select('id', 'mentor_id', 'rating', 'comment', 'created_at')
                          ->latest('created_at')
                          ->limit(5);
                },
                'latestMentorDocument'
            ]);
        }

        // Preparar datos del certificado para estudiantes
        $certificateData = null;
        if ($user->role === 'student' && $user->latestStudentDocument) {
            $certificateData = [
                'id' => $user->latestStudentDocument->id,
                'status' => $user->latestStudentDocument->status,
                'uploaded_at' => $user->latestStudentDocument->created_at->diffForHumans(),
                'processed_at' => $user->latestStudentDocument->processed_at?->diffForHumans(),
                'rejection_reason' => $user->latestStudentDocument->rejection_reason,
                'keyword_score' => $user->latestStudentDocument->keyword_score,
            ];
        }

        // Preparar datos del CV para mentores
        $mentorCvData = null;
        $cvVerified = false;
        if ($user->role === 'mentor') {
            $cvVerified = $user->mentor?->cv_verified ?? false;
            
            if ($user->latestMentorDocument) {
                $mentorCvData = [
                    'id' => $user->latestMentorDocument->id,
                    'user_id' => $user->id,
                    'status' => $user->latestMentorDocument->status,
                    'created_at' => $user->latestMentorDocument->created_at,
                    'processed_at' => $user->latestMentorDocument->processed_at,
                    'rejection_reason' => $user->latestMentorDocument->rejection_reason,
                    'keyword_score' => $user->latestMentorDocument->keyword_score,
                    'is_public' => $user->latestMentorDocument->is_public,
                ];
            }
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'certificate' => $certificateData,
            'mentorCv' => $mentorCvData,
            'cvVerified' => $cvVerified,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function getAreasInteres()
    {
        // Incluir roadmap_url para mostrar el botón "Ruta" en el front
        $areas = AreaInteres::all(['id', 'nombre', 'descripcion', 'roadmap_url']);
    
        return response()->json($areas);
    }

    /**
     * Get fresh mentor calificación (not cached)
     * Used in profile update form to show real-time rating
     */
    public function getMentorCalificacion()
    {
        $mentor = Auth::user()->mentor;
        
        if (!$mentor) {
            return response()->json(['calificacionPromedio' => 0]);
        }

        // Always refresh to get fresh value from DB (not cached)
        $mentor->refresh();
        
        return response()->json([
            'calificacionPromedio' => (float) $mentor->calificacionPromedio
        ]);
    }

    /**
     * Get fresh mentor availability status from DB.
     */
    public function getMentorDisponibilidad()
    {
        $mentor = Auth::user()->mentor;
        
        if (!$mentor) {
            return response()->json(['disponible_ahora' => false]);
        }

        // Always refresh to get fresh value from DB (not cached)
        $mentor->refresh();
        
        return response()->json([
            'disponible_ahora' => (bool) $mentor->disponible_ahora
        ]);
    }

    /**
     * Update the aprendiz profile information.
     */
    public function updateAprendizProfile(Request $request): RedirectResponse
    {
        // Validación
        $validated = $request->validate([
            'semestre' => 'required|integer|min:1|max:10',
            'objetivos' => 'nullable|string|max:1000',
            'areas_interes' => 'required|array|min:1',
            'areas_interes.*' => 'exists:areas_interes,id'
        ]);

        // Obtener o crear perfil de aprendiz
        $aprendiz = Auth::user()->aprendiz ?? new Aprendiz(['user_id' => Auth::id()]);
        
        // Actualizar datos
        $aprendiz->fill($validated);
        $aprendiz->save();
        
        // Sincronizar áreas de interés (many-to-many)
        $aprendiz->areasInteres()->sync($validated['areas_interes']);
        
        // INVALIDAR CACHÉ: Completitud de perfil para el usuario autenticado
        Cache::forget('profile_completeness_' . Auth::id());
        
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the mentor profile information.
     */
    public function updateMentorProfile(Request $request): RedirectResponse
    {
        // Validación compleja según criterios de aceptación
        $validated = $request->validate([
            'experiencia' => [
                'required',
                'string',
                'min:50',
                'max:2000',
                function ($attribute, $value, $fail) {
                    if (str_word_count($value) < 10) {
                        $fail('La experiencia debe contener al menos 10 palabras descriptivas.');
                    }
                }
            ],
            'biografia' => [
                'required',
                'string',
                'min:100',
                'max:1000',
            ],
            'años_experiencia' => [
                'required',
                'integer',
                'min:1',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    // Validar coherencia entre años y descripción
                    $experienciaText = $request->input('experiencia', '');
                    if ($value >= 10 && !preg_match('/senior|líder|lead|manager|director/i', $experienciaText)) {
                        $fail('Con ' . $value . ' años de experiencia se esperan roles senior o de liderazgo en la descripción.');
                    }
                }
            ],
            'disponibilidad' => 'required|string|min:10|max:200',
            'disponibilidad_detalle' => 'nullable|string|max:500',
            'areas_especialidad' => [
                'required',
                'array',
                'min:1',
                'max:5'
            ],
            'areas_especialidad.*' => 'exists:areas_interes,id'
        ], [
            // Mensajes personalizados
            'experiencia.min' => 'La descripción de experiencia debe ser más detallada (mínimo 50 caracteres).',
            'biografia.min' => 'La biografía debe ser más completa (mínimo 100 caracteres).',
            'años_experiencia.min' => 'Debe tener al menos 1 año de experiencia.',
            'años_experiencia.max' => 'El máximo permitido es 50 años de experiencia.',
            'areas_especialidad.min' => 'Debe seleccionar al menos 1 área de especialidad.',
            'areas_especialidad.max' => 'Máximo 5 áreas de especialidad permitidas.',
        ]);

        // Obtener o crear perfil de mentor
        $mentor = Auth::user()->mentor ?? new \App\Models\Mentor(['user_id' => Auth::id()]);
        
        // Actualizar datos (excluyendo areas_especialidad que se maneja por separado)
        $mentorData = collect($validated)->except('areas_especialidad')->toArray();
        $mentor->fill($mentorData);
        $mentor->save();
        
        // Sincronizar áreas de especialidad (many-to-many)
        $mentor->areasInteres()->sync($validated['areas_especialidad']);
        
        // INVALIDAR CACHÉ: Completitud de perfil para el usuario autenticado
        Cache::forget('profile_completeness_' . Auth::id());
        
        return Redirect::route('profile.edit')->with('status', 'mentor-profile-updated');
    }

    /**
     * Toggle mentor availability status.
     */
    public function toggleMentorDisponibilidad(Request $request): RedirectResponse
    {
        try {
            Log::info('🔴 [TOGGLE] START - Toggle request received', [
                'user_id' => Auth::id(),
                'request_disponible' => $request->input('disponible'),
            ]);

            $user = Auth::user();
            $mentor = $user->mentor;

            if (!$mentor) {
                Log::error('🔴 [TOGGLE] Mentor not found', ['user_id' => Auth::id()]);
                return Redirect::route('profile.edit')->withErrors([
                    'mentor' => 'Perfil de mentor no encontrado.'
                ]);
            }

            // Parsear el booleano correctamente
            $newDisponible = $request->input('disponible') === true 
                           || $request->input('disponible') === 'true' 
                           || $request->input('disponible') == 1;

            Log::info('🔴 [TOGGLE] Parsed disponible value', [
                'input' => $request->input('disponible'),
                'input_type' => gettype($request->input('disponible')),
                'parsed_as' => $newDisponible,
            ]);

            // Si intenta ACTIVAR disponibilidad, validar perfil completo
            if ($newDisponible) {
                // Validar que el perfil esté completo
                if (empty($mentor->experiencia) || strlen($mentor->experiencia) < 50 ||
                    empty($mentor->biografia) || strlen($mentor->biografia) < 100 ||
                    empty($mentor->disponibilidad) ||
                    $mentor->años_experiencia <= 0) {
                    
                    Log::info('🔴 [TOGGLE] Profile incomplete', [
                        'experiencia_len' => strlen($mentor->experiencia ?? ''),
                        'biografia_len' => strlen($mentor->biografia ?? ''),
                        'disponibilidad' => $mentor->disponibilidad,
                        'años_experiencia' => $mentor->años_experiencia,
                    ]);

                    return Redirect::route('profile.edit')->withErrors([
                        'disponibilidad' => 'Debes completar tu perfil de mentor antes de activar disponibilidad.'
                    ]);
                }

                // Validar CV
                if (!$mentor->cv_verified) {
                    // Verificar si tiene CV aprobado
                    $hasApprovedCV = $user->mentorDocuments()
                        ->where('status', 'approved')
                        ->exists();

                    Log::info('🔴 [TOGGLE] CV verification check', [
                        'cv_verified_flag' => $mentor->cv_verified,
                        'has_approved_cv' => $hasApprovedCV,
                    ]);

                    if (!$hasApprovedCV) {
                        return Redirect::route('profile.edit')
                            ->withErrors([
                                'cv_verification' => 'Debes verificar tu CV para ofrecer mentorías.'
                            ])
                            ->with('cv_upload_required', [
                                'action' => 'upload_cv',
                                'upload_url' => route('mentor.cv.upload')
                            ]);
                    } else {
                        // Auto-marcar como verificado si tiene CV aprobado
                        $mentor->update(['cv_verified' => true]);
                        Log::info('🔴 [TOGGLE] Auto-marked CV as verified', ['mentor_id' => $mentor->id]);
                    }
                }
            }

            // Hacer el toggle
            $mentor->disponible_ahora = $newDisponible;
            $mentor->save();

            Log::info('🔴 [TOGGLE] SUCCESS - DB updated', [
                'mentor_id' => $mentor->id,
                'disponible_ahora_in_db' => $newDisponible,
            ]);

            $mentor->refresh();
            Log::info('🔴 [TOGGLE] After refresh', [
                'disponible_ahora_from_db' => $mentor->disponible_ahora,
            ]);

            // Invalidar caché de perfil
            Cache::forget('profile_completeness_' . Auth::id());

            // CRÍTICO: Invalidar cachés de sugerencias incrementando versión global
            // Esto invalida TODOS los cachés de sugerencias de forma eficiente
            Cache::increment('mentor_suggestions_version');
            
            Log::info('🗑️ [CACHE] Incremented mentor suggestions version', [
                'mentor_id' => $mentor->id,
                'new_version' => Cache::get('mentor_suggestions_version'),
            ]);

            $message = $newDisponible 
                ? 'Ahora estás disponible para mentoría.' 
                : 'Has pausado tu disponibilidad.';

            Log::info('🔴 [TOGGLE] COMPLETE - Redirecting with message', [
                'message' => $message,
            ]);

            return Redirect::route('profile.edit')->with('status', $message);

        } catch (\Exception $e) {
            Log::error('🔴 [TOGGLE] EXCEPTION CAUGHT', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Redirect::route('profile.edit')->withErrors([
                'disponibilidad' => 'Error al cambiar disponibilidad: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
